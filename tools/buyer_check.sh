#!/bin/bash
# Buyer check: install Broadquill Lite from the release archive (git archive of HEAD, what
# Composer users receive) into a fresh Laravel app with Filament, then run the real UI flow
# in headless Chrome. Usage: tools/buyer_check.sh <php-binary> <filament-constraint> <workdir> [port]
set -euo pipefail
PHP_BIN=$1; FILAMENT=$2; WORK=$3; PORT=${4:-8766}
ROOT=$(cd "$(dirname "$0")/.." && pwd)
COMPOSER="$(command -v composer)"
step() { echo "STEP $1: $2"; }
rm -rf "$WORK" && mkdir -p "$WORK"
step 1 "release archive"
git -C "$ROOT" archive --format=zip --prefix=filament-broadquill-lite/ -o "$WORK/filament-broadquill-lite.zip" HEAD
(cd "$WORK" && unzip -q filament-broadquill-lite.zip)
test ! -d "$WORK/filament-broadquill-lite/tests" && test ! -d "$WORK/filament-broadquill-lite/docs" && echo "  archive has no tests/docs: ok"
step 2 "fresh Laravel app"
"$PHP_BIN" "$COMPOSER" create-project -q --no-interaction laravel/laravel "$WORK/app" "^13.0"
cd "$WORK/app"
"$PHP_BIN" "$COMPOSER" require -q --no-interaction "filament/filament:$FILAMENT"
"$PHP_BIN" artisan filament:install --panels --no-interaction >/dev/null
step 3 "composer require from the archive"
"$PHP_BIN" "$COMPOSER" config repositories.broadquill "{\"type\":\"path\",\"url\":\"$WORK/filament-broadquill-lite\",\"options\":{\"symlink\":false,\"versions\":{\"pnscripts/filament-broadquill-lite\":\"1.0.0\"}}}"
"$PHP_BIN" "$COMPOSER" require -q --no-interaction "pnscripts/filament-broadquill-lite:^1.0"
"$PHP_BIN" artisan package:discover | grep -q "pnscripts/filament-broadquill-lite" && echo "  provider discovered: ok"
"$PHP_BIN" artisan tinker --execute="echo __('broadquill-lite::bulk-edit.label');" | grep -q "Edit selected" && echo "  translations: ok"
step 4 "demo resource from the Quick start"
cp -r "$ROOT/tools/buyer-app/." "$WORK/app/"
sed -i "s#->path('admin')#->path('admin')->brandName('Demo Shop')#" app/Providers/Filament/AdminPanelProvider.php
"$PHP_BIN" artisan migrate:fresh --seed --force -q
step 5 "real UI in Chrome"
# PHP's built-in server directly (artisan serve drops most environment variables).
(cd public && setsid "$PHP_BIN" -S "127.0.0.1:$PORT" ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php > "$WORK/serve.log" 2>&1 & echo $! > "$WORK/serve.pid")
sleep 3
trap 'kill -- -"$(ps -o pgid= -p "$(cat "$WORK/serve.pid")" | tr -d " ")" 2>/dev/null || true' EXIT
BASE="http://127.0.0.1:$PORT" node "$ROOT/tools/buyer-ui.mjs"
echo "BUYER CHECK PASSED"
