#!/usr/bin/env bash
set -x
# Usage: build.sh [<location-of-functions-file-to-include> [<work-dir>]]
[[ -z ${1:-""} ]] && set -eu || source ${1}/_functions.sh --vendor
cd ${2:-"."}

export APP_VERSION=$(versionFromGit 2>/dev/null || echo "")
echo "=== Building v${APP_VERSION} of ${SERVICE_NAME} ==="

composer install
bin/console doctrine:migrations:migrate
bin/console aduser:update
