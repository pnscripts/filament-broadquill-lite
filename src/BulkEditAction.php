<?php

declare(strict_types=1);

namespace PNScripts\BroadquillLite;

use Closure;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Resources\Pages\Page as ResourcePage;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Number;
use LogicException;
use PNScripts\BroadquillLite\Exceptions\BulkEditRolledBack;
use PNScripts\BroadquillLite\Support\AttributePath;
use PNScripts\BroadquillLite\Support\ColumnInspector;
use Throwable;

/**
 * Bulk edit for Filament tables: the user ticks the fields to change, sets a value (or clears it)
 * and every selected record is updated through Eloquent, one by one, with the update policy checked
 * for each record.
 */
class BulkEditAction extends BulkAction
{
    use CanCustomizeProcess;

    public const string STATE_KEY = 'broadquill';

    public const string MODE_SET = 'set';

    public const string MODE_CLEAR = 'clear';

    /**
     * @var array<Field> | Closure
     */
    protected array | Closure $editableFields = [];

    /**
     * @var array<int, Field> | null
     */
    protected ?array $cachedEditableFields = null;

    /**
     * null = decide from the database column (nullable columns can be cleared).
     *
     * @var bool | array<string> | Closure | null
     */
    protected bool | array | Closure | null $clearable = null;

    protected bool | Closure $isAtomic = false;

    protected ?Closure $mutateChangesUsing = null;

    protected ?Closure $afterRecordUpdated = null;

    protected int $changedRecordsCount = 0;

    protected int $unchangedRecordsCount = 0;

    public static function getDefaultName(): ?string
    {
        return 'bulkEdit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('broadquill-lite::bulk-edit.label'));

        $this->icon(Heroicon::OutlinedPencilSquare);

        $this->modalIcon(Heroicon::OutlinedPencilSquare);

        $this->modalHeading(fn (): string => __('broadquill-lite::bulk-edit.modal.heading', [
            'label' => $this->getTitleCasePluralModelLabel(),
        ]));

        $this->modalDescription(__('broadquill-lite::bulk-edit.modal.description'));

        $this->modalSubmitActionLabel(__('broadquill-lite::bulk-edit.modal.actions.apply.label'));

        $this->modalWidth('2xl');

        $this->schema(fn (): array => $this->getBulkEditSchema());

        $this->authorizeIndividualRecords(fn (Model $record): Response => $this->getUpdateAuthorizationResponse($record));

        $this->successNotificationTitle(__('broadquill-lite::bulk-edit.notifications.updated.title'));

        $this->successNotification(fn (Notification $notification): Notification => $notification->body($this->getResultSummary()));

        $this->failureNotificationTitle(function (int $successCount, int $totalCount): string {
            if ($successCount) {
                return __('broadquill-lite::bulk-edit.notifications.partial.title', [
                    'count' => Number::format($successCount),
                    'total' => Number::format($totalCount),
                ]);
            }

            return __('broadquill-lite::bulk-edit.notifications.none.title');
        });

        $this->missingBulkAuthorizationFailureNotificationMessage(fn (int $failureCount): string => trans_choice(
            'broadquill-lite::bulk-edit.notifications.missing_authorization',
            $failureCount,
            ['count' => Number::format($failureCount)],
        ));

        $this->missingBulkProcessingFailureNotificationMessage(fn (int $failureCount): string => trans_choice(
            'broadquill-lite::bulk-edit.notifications.missing_processing',
            $failureCount,
            ['count' => Number::format($failureCount)],
        ));

        $this->action(function (array $data): void {
            $this->applyBulkEdit($data);
        });

        $this->deselectRecordsAfterCompletion();
    }

    /**
     * The form fields the user may change, e.g. [Select::make('status')->options([...])].
     *
     * @param  array<Field> | Closure  $fields
     */
    public function fields(array | Closure $fields): static
    {
        $this->editableFields = $fields;
        $this->cachedEditableFields = null;

        return $this;
    }

    /**
     * Which fields offer "Clear". Default: the fields whose database column is nullable.
     *
     * @param  bool | array<string> | Closure | null  $condition
     */
    public function clearable(bool | array | Closure | null $condition = true): static
    {
        $this->clearable = $condition;

        return $this;
    }

    /**
     * All or nothing: when one record cannot be updated (a policy denies it, a model event
     * cancels the save or the database throws), every change of this run is rolled back.
     */
    public function atomic(bool | Closure $condition = true): static
    {
        $this->isAtomic = $condition;

        return $this;
    }

    /**
     * Adjust the changes for one record before they are saved, e.g. to stamp "updated_by".
     * Receives $changes and $record; returns the changes to fill.
     */
    public function mutateChangesUsing(?Closure $callback): static
    {
        $this->mutateChangesUsing = $callback;

        return $this;
    }

    /**
     * Runs after each record has been saved with real changes. Receives $record and $changes.
     */
    public function afterRecordUpdated(?Closure $callback): static
    {
        $this->afterRecordUpdated = $callback;

        return $this;
    }

    public function isAtomic(): bool
    {
        return (bool) $this->evaluate($this->isAtomic);
    }

    /**
     * @return array<int, Field>
     */
    public function getEditableFields(): array
    {
        if ($this->cachedEditableFields !== null) {
            return $this->cachedEditableFields;
        }

        $fields = $this->evaluate($this->editableFields) ?? [];

        if (! is_array($fields)) {
            throw new LogicException('Broadquill: fields() must be given an array of form fields, or a closure that returns one.');
        }

        $fields = array_values($fields);

        $names = [];

        foreach ($fields as $field) {
            if (! $field instanceof Field) {
                throw new LogicException('Broadquill: every entry passed to fields() must be a Filament form field (for example TextInput, Select or Toggle).');
            }

            $name = $field->getName();

            if (isset($names[$name])) {
                throw new LogicException("Broadquill: the field [{$name}] is listed twice in fields().");
            }

            if ($this->isManyToManyField($field)) {
                throw new LogicException("Broadquill: the field [{$name}] edits a many-to-many relationship, which this action does not support. Use a single-value field or a JSON/array column.");
            }

            $names[$name] = true;
        }

        return $this->cachedEditableFields = $fields;
    }

    public function isFieldClearable(Field $field): bool
    {
        $clearable = $this->evaluate($this->clearable);

        if (is_array($clearable)) {
            return in_array($field->getName(), $clearable, true);
        }

        if (is_bool($clearable)) {
            return $clearable;
        }

        if ($this->isBooleanField($field)) {
            return false;
        }

        if (AttributePath::isJsonPath($field->getName())) {
            return true;
        }

        return ColumnInspector::isNullable($this->getModelInstanceForInspection(), $field->getName()) === true;
    }

    public function getChangedRecordsCount(): int
    {
        return $this->changedRecordsCount;
    }

    public function getUnchangedRecordsCount(): int
    {
        return $this->unchangedRecordsCount;
    }

    /**
     * Turns the submitted modal state into [attribute => new value] for the ticked fields.
     *
     * @param  array<mixed>  $data  the modal state
     * @return array<string, mixed>
     */
    public function extractChanges(array $data): array
    {
        $changes = [];

        foreach ($this->getEditableFields() as $index => $field) {
            $key = static::fieldKey($index);

            if (! data_get($data, static::STATE_KEY . ".apply.{$key}")) {
                continue;
            }

            $name = $field->getName();
            $mode = data_get($data, static::STATE_KEY . ".mode.{$key}") ?? static::MODE_SET;

            $changes[AttributePath::attribute($name)] = (($mode === static::MODE_CLEAR) && $this->isFieldClearable($field))
                ? null
                : data_get($data, $name);
        }

        return $changes;
    }

    public static function fieldKey(int $index): string
    {
        return "f{$index}";
    }

    /**
     * @return array<Component>
     */
    protected function getBulkEditSchema(): array
    {
        $rows = [];

        foreach ($this->getEditableFields() as $index => $field) {
            $rows[] = $this->makeFieldRow($field, static::fieldKey($index));
        }

        return $rows;
    }

    protected function makeFieldRow(Field $field, string $key): Component
    {
        $applyPath = static::STATE_KEY . ".apply.{$key}";
        $modePath = static::STATE_KEY . ".mode.{$key}";
        $clearable = $this->isFieldClearable($field);

        $field->hiddenLabel();

        if ((! $clearable) && (! $this->isBooleanField($field)) && (! $field->isRequired()) && ($this->evaluate($this->clearable) === null)) {
            // The column is NOT NULL: an empty value would fail in the database, so ask for one.
            $field->required(ColumnInspector::isNullable($this->getModelInstanceForInspection(), $field->getName()) === false);
        }

        $valueComponents = [];

        if ($clearable) {
            $valueComponents[] = ToggleButtons::make($modePath)
                ->hiddenLabel()
                ->options([
                    static::MODE_SET => __('broadquill-lite::bulk-edit.fields.mode.set'),
                    static::MODE_CLEAR => __('broadquill-lite::bulk-edit.fields.mode.clear'),
                ])
                ->default(static::MODE_SET)
                ->inline()
                ->grouped()
                ->live();
        }

        $valueComponents[] = Group::make([$field])
            ->visible(fn (Get $get): bool => ! ($clearable && ($get($modePath) === static::MODE_CLEAR)));

        $valueComponents[] = Text::make(__('broadquill-lite::bulk-edit.fields.cleared'))
            ->color('gray')
            ->visible(fn (Get $get): bool => $clearable && ($get($modePath) === static::MODE_CLEAR));

        return Grid::make(['default' => 1, 'sm' => 5])
            ->schema([
                Checkbox::make($applyPath)
                    ->label(fn (): string | \Illuminate\Contracts\Support\Htmlable | null => $field->getLabel())
                    ->live()
                    ->columnSpan(['default' => 1, 'sm' => 2]),
                Text::make(__('broadquill-lite::bulk-edit.fields.keep'))
                    ->color('gray')
                    ->visible(fn (Get $get): bool => ! $get($applyPath))
                    ->columnSpan(['default' => 1, 'sm' => 3]),
                Group::make($valueComponents)
                    ->visible(fn (Get $get): bool => (bool) $get($applyPath))
                    ->columnSpan(['default' => 1, 'sm' => 3]),
            ]);
    }

    /**
     * @param  array<mixed>  $data  the modal state
     */
    protected function applyBulkEdit(array $data): void
    {
        $changes = $this->extractChanges($data);

        if ($changes === []) {
            Notification::make()
                ->warning()
                ->title(__('broadquill-lite::bulk-edit.notifications.nothing.title'))
                ->body(__('broadquill-lite::bulk-edit.notifications.nothing.body'))
                ->send();

            $this->halt();
        }

        $this->changedRecordsCount = 0;
        $this->unchangedRecordsCount = 0;

        $this->process(function (EloquentCollection | Collection | LazyCollection $records) use ($changes): void {
            if (! $this->isAtomic()) {
                $this->updateRecords($records, $changes, atomic: false);

                return;
            }

            try {
                $this->getModelInstanceForInspection()->getConnection()->transaction(function () use ($records, $changes): void {
                    $this->updateRecords($records, $changes, atomic: true);

                    if ($this->successfulSelectedRecordsCount < $this->getTotalSelectedRecordsCount()) {
                        throw new BulkEditRolledBack('Not every selected record could be updated.');
                    }
                });
            } catch (Throwable $exception) {
                if ($exception instanceof LogicException) {
                    throw $exception;
                }

                if (! $exception instanceof BulkEditRolledBack) {
                    report($exception);
                }

                $this->changedRecordsCount = 0;
                $this->unchangedRecordsCount = 0;

                $this->reportCompleteBulkProcessingFailure(
                    'broadquill_atomic',
                    __('broadquill-lite::bulk-edit.notifications.rolled_back'),
                );
            }
        }, ['changes' => $changes]);
    }

    /**
     * @param  iterable<Model>  $records
     * @param  array<string, mixed>  $changes
     */
    protected function updateRecords(iterable $records, array $changes, bool $atomic): void
    {
        $isFirstException = true;

        foreach ($records as $record) {
            try {
                $recordChanges = $this->mutateChangesUsing
                    ? $this->evaluate($this->mutateChangesUsing, ['changes' => $changes, 'record' => $record], [Model::class => $record, $record::class => $record])
                    : $changes;

                $recordChanges = $this->normalizeChanges($recordChanges);

                $this->guardFillable($record, $recordChanges);

                $record->fill($recordChanges);

                if (! $record->isDirty()) {
                    $this->unchangedRecordsCount++;

                    continue;
                }

                if (! $record->save()) {
                    if ($atomic) {
                        throw new BulkEditRolledBack('A model event cancelled the save.');
                    }

                    $this->reportBulkProcessingFailure();

                    continue;
                }

                $this->changedRecordsCount++;

                if ($this->afterRecordUpdated) {
                    $this->evaluate($this->afterRecordUpdated, ['changes' => $recordChanges, 'record' => $record], [Model::class => $record, $record::class => $record]);
                }
            } catch (Throwable $exception) {
                if ($atomic || ($exception instanceof LogicException)) {
                    throw $exception;
                }

                $this->reportBulkProcessingFailure();

                if ($isFirstException) {
                    report($exception);

                    $isFirstException = false;
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeChanges(mixed $changes): array
    {
        if (! is_array($changes)) {
            throw new LogicException('Broadquill: mutateChangesUsing() must return the array of changes to save.');
        }

        $normalized = [];

        foreach ($changes as $attribute => $value) {
            if (! is_string($attribute)) {
                throw new LogicException('Broadquill: mutateChangesUsing() must return an array keyed by attribute name.');
            }

            $normalized[$attribute] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    protected function guardFillable(Model $model, array $changes): void
    {
        foreach (array_keys($changes) as $attribute) {
            if (! $model->isFillable($attribute)) {
                throw new LogicException(sprintf(
                    'Broadquill: [%s] is not mass assignable on [%s]. Add "%s" to the model\'s $fillable (or unguard the model) so bulk edit can write it.',
                    $attribute,
                    $model::class,
                    $attribute,
                ));
            }
        }
    }

    protected function getUpdateAuthorizationResponse(Model $record): Response
    {
        $livewire = $this->getLivewire();

        if (($livewire instanceof RelationManager) || ($livewire instanceof ManageRelatedRecords)) {
            return $livewire->getAuthorizationResponse('update', $record);
        }

        if ($livewire instanceof ResourcePage) {
            $resource = $livewire::getResource();

            if (is_a($resource, Resource::class, allow_string: true)) {
                return $resource::getEditAuthorizationResponse($record);
            }
        }

        $policy = Gate::getPolicyFor($record);

        if (is_object($policy) && method_exists($policy, 'update')) {
            return Gate::inspect('update', $record);
        }

        return Response::allow();
    }

    protected function getResultSummary(): string
    {
        $parts = [];

        if ($this->changedRecordsCount) {
            $parts[] = trans_choice('broadquill-lite::bulk-edit.notifications.updated.changed', $this->changedRecordsCount, [
                'count' => Number::format($this->changedRecordsCount),
            ]);
        }

        if ($this->unchangedRecordsCount) {
            $parts[] = trans_choice('broadquill-lite::bulk-edit.notifications.updated.unchanged', $this->unchangedRecordsCount, [
                'count' => Number::format($this->unchangedRecordsCount),
            ]);
        }

        return implode(' ', $parts);
    }

    protected function getModelInstanceForInspection(): Model
    {
        $model = $this->getModel();

        if (blank($model)) {
            throw new LogicException('Broadquill: the bulk edit action could not find the table\'s model.');
        }

        $instance = app($model);

        if (! $instance instanceof Model) {
            throw new LogicException("Broadquill: [{$model}] is not an Eloquent model.");
        }

        return $instance;
    }

    protected function isBooleanField(Field $field): bool
    {
        return ($field instanceof Toggle) || ($field instanceof Checkbox);
    }

    protected function isManyToManyField(Field $field): bool
    {
        if ($field instanceof CheckboxList) {
            return filled($field->getRelationshipName());
        }

        if ($field instanceof Select) {
            return $field->isMultiple() && filled($field->getRelationshipName());
        }

        return false;
    }
}
