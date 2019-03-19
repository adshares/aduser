#!/usr/bin/env bash
source ${1}/_functions.sh
[[ -z ${2:-""} ]] || cd $2

composer install
#bin/console aduser:update #todo: +cron
