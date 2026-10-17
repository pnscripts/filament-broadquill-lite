# Changelog

All notable changes to Broadquill Lite are listed here. The project follows [Semantic Versioning](https://semver.org/).

## 1.0.0 - 2026-10-17

First release.

- `BulkEditAction` for Filament 4 and 5 tables: tick the fields to change, set or clear their values, apply to every selected record.
- Clear is offered for nullable columns only, read from the database schema; `NOT NULL` columns ask for a value.
- The `update` authorization is checked for every record (resource, relation manager or model policy); skipped records are counted in the notification.
- Records that already have the new values are not saved again and are reported.
- `atomic()` rolls every change back when one record fails.
- `mutateChangesUsing()`, `afterRecordUpdated()` and `clearable()` options; dot notation writes one key of a JSON column.
- English and Bulgarian translations.
