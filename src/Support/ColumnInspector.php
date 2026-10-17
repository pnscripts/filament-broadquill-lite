<?php

declare(strict_types=1);

namespace PNScripts\BroadquillLite\Support;

use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Reads column nullability from the database schema, once per table.
 */
final class ColumnInspector
{
    /**
     * @var array<string, array<string, bool>>
     */
    private static array $cache = [];

    /**
     * True or false when the column is known, null when the attribute is not a plain column
     * (an accessor, a relationship, a JSON path) or the schema cannot be read.
     */
    public static function isNullable(Model $model, string $attribute): ?bool
    {
        $columns = self::columns($model);

        return $columns[$attribute] ?? null;
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    /**
     * @return array<string, bool>
     */
    private static function columns(Model $model): array
    {
        $key = ($model->getConnectionName() ?? 'default') . '|' . $model->getTable();

        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        try {
            $columns = [];

            foreach ($model->getConnection()->getSchemaBuilder()->getColumns($model->getTable()) as $column) {
                $columns[(string) $column['name']] = (bool) $column['nullable'];
            }
        } catch (Throwable) {
            $columns = [];
        }

        return self::$cache[$key] = $columns;
    }
}
