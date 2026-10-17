# Broadquill Lite: directory listings

Status 2026-10-17: built, tested (48 Pest tests, PHPStan max), buyer check passed from the release archive on Filament 4.15.2 (PHP 8.3) and 5.10.2 (PHP 8.4). Public repository https://github.com/pnscripts/filament-broadquill-lite, tag v1.0.0.

## Packagist (owner step: sign-in)

1. Sign in at https://packagist.org with the PN Scripts account (PNScripts).
2. Submit → paste `https://github.com/pnscripts/filament-broadquill-lite` → Check → Submit.
3. On the package page, follow "enable the GitHub hook" so new tags publish themselves (or use the GitHub integration of the PNScripts account).
4. Check: the page shows `pnscripts/filament-broadquill-lite`, author PN Scripts <office@pnscripts.com>, licence MIT, version 1.0.0; `composer require pnscripts/filament-broadquill-lite` works in a fresh Laravel 13 app.

## Filament plugin directory (owner step: account + author profile)

The directory is managed on filamentphp.com (not by pull request any more). An author profile is requested at https://filamentphp.com/author with a filamentphp.com account; after approval, plugins are submitted in the author area. The plugin must be on Packagist first.

Fields to enter:

| Field | Value |
|---|---|
| Author name | PN Scripts |
| Author website | https://pnscripts.com |
| Author GitHub | pnscripts |
| Plugin name | Broadquill Lite |
| Short description | Bulk edit for Filament tables: tick the fields to change, set or clear the values and apply to every selected record, with per-record policy checks and a clear summary. |
| GitHub repository | pnscripts/filament-broadquill-lite |
| Documentation URL | https://raw.githubusercontent.com/pnscripts/filament-broadquill-lite/main/README.md |
| Price | Free |
| Supported Filament versions | 4.x, 5.x (composer: ^4.13 \| ^5.8) |
| Categories / tags | Tables, Action |
| Dark mode | Yes (built only from Filament components; screenshot docs/images/dark.jpg) |
| Translations | Yes (publishable language files; English and Bulgarian included) |
| Image (16:9, at least 2560x1440, light theme, plugin running) | listing/filament-directory.jpg (2560x1440) |

Checkbox notes (each one is a public claim): dark mode and translations are both true and verified; do not tick anything else that has not been checked.

## Not verified

- Filament releases before 4.13 and 5.8 (they failed in the test harness with the current Livewire, so composer excludes them).
- PostgreSQL and SQL Server: the suite runs on SQLite and MySQL 8.0.46 only. The action uses only Eloquent and the schema builder's `getColumns()`, which Laravel supports on all of them.
