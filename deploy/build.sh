#!/usr/bin/env bash
set -e

source ${1:-$(dirname $(readlink -f "$0"))/bin}/_functions.sh
[[ -z ${2:-""} ]] || cd $2

composer install
#bin/console aduser:update #todo: +cron
