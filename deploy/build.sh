#!/usr/bin/env bash
source ${1}/_functions.sh --vendor
[[ -z ${2:-""} ]] || cd $2

export APP_VERSION=$(versionFromGit)

composer install
bin/console aduser:update
