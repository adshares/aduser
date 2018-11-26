#!/usr/bin/env bash

set -e

# Ubuntu 18.04 only

# Install dependencies for python operations
apt-get -qq -y install --no-install-recommends python python-pip python-dev gcc

pip install pipenv

if [ -v BUILD_WITH_PYBROWSCAP ]; then
    pipenv run install_pybrowscap
fi

if [ -v BUILD_WITH_GEOIP ]; then
    pipenv run install_geoip
fi
