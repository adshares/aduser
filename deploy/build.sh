#!/usr/bin/env bash

# Usage: build.sh [<work-dir>]
cd ${1:-"."}

composer install --no-dev

yarn install
yarn run build
