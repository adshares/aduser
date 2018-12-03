#!/usr/bin/env bash

set -e

env | sort

if [ ! -v TRAVIS ]; then
  # Checkout repo and change directory

  # Install git
  git --version || apt-get install -y git

  git clone \
    --depth=1 \
    https://github.com/adshares/aduser.git \
    --branch ${BUILD_BRANCH:-master} \
    ${BUILD_PATH}/build

  cd ${BUILD_PATH}/build
fi

envsubst < .env.dist | tee .env

if [ ${ADUSER_APP_ENV} == 'dev' ]; then
    pipenv install --dev pipenv
elif [ ${ADUSER_APP_ENV} == 'deploy' ]; then
    pipenv install --deploy pipenv
else
    pipenv install pipenv
fi

if [ -v BUILD_WITH_PYBROWSCAP ]; then
    pipenv run install_pybrowscap
fi

if [ -v BUILD_WITH_GEOIP ]; then
    pipenv run install_geoip
fi
