#!/usr/bin/env bash
set -e

source ${1:-$(dirname $(readlink -f "$0"))/bin}/_functions.sh

composer install
