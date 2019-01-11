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

if [[ ${ADUSER_APP_ENV:-dev} == "dev" ]]; then
    pipenv install --dev pipenv
elif [[ ${ADUSER_APP_ENV} == "deploy" ]]; then
    pipenv install --deploy pipenv
else
    pipenv install pipenv
fi
