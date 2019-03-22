#!/usr/bin/env bash
if [[ -z ${1:-""} ]]
then
    set -eu
else
    source ${1}/_functions.sh --vendor
fi
cd ${2:-"."}

export APP_VERSION=$(versionFromGit)

composer install
bin/console doctrine:migrations:migrate
bin/console aduser:update
