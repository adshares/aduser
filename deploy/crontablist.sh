#!/usr/bin/env bash
set -eu

SERVICE_DIR=${SERVICE_DIR:-$(dirname $(dirname $(readlink -f $0)))}

echo "0 0 * * * php ${SERVICE_DIR}/bin/console aduser:update &> /dev/null"
