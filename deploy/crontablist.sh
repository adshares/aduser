#!/usr/bin/env bash
set -eu

SERVICE_DIR=${SERVICE_DIR:-$(dirname "$(dirname "$(readlink -f "$0")")")}

echo -n "0 0 * * * "
echo -n "php ${SERVICE_DIR}/bin/console ops:update"
echo ""

echo -n "*/10 * * * * "
echo -n "php ${SERVICE_DIR}/bin/console ops:domains:scan"
echo ""
