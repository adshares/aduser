#!/usr/bin/env bash
# Usage: build.sh [<location-of-functions-file-to-include> [<work-dir>]]
[[ -z ${1:-""} ]] && set -eu || source ${1}/_functions.sh --vendor
cd ${2:-"."}

export APP_VERSION=$(versionFromGit 2>/dev/null || echo "")
echo "=== Building v${APP_VERSION} of ${SERVICE_NAME} ==="

composer install --no-dev --optimize-autoloader
bin/console doctrine:migrations:migrate --no-interaction

if [[ ${_UPDATE_DATA:-1} -eq 1 ]]
then
    bin/console aduser:update
fi
