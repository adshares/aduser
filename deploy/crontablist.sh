#!/usr/bin/env bash
set -eu

SERVICE_DIR=${SERVICE_DIR:-$(dirname $(dirname $(readlink -f $0)))}
LOG_DIR=${LOG_DIR:-""}

if [[ -z ${LOG_DIR} ]]
then
    _REDIRECTION="&> /dev/null"
else
    _REDIRECTION="&>> ${LOG_DIR}/aduser-crontab.log"
fi

echo "0 0 * * * php ${SERVICE_DIR}/bin/console aduser:update"
