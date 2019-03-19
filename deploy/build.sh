#!/usr/bin/env bash
source ${1}/_functions.sh --vendor
[[ -z ${2:-""} ]] || cd $2

[[ ${DRY_RUN:-0} -eq 1 ]] && echo "DRY-RUN: $0 $@" && exit 127

composer install
bin/console aduser:update #todo: +cron
