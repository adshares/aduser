#!/usr/bin/env bash

set -e

HERE=$(dirname $(readlink -f "$0"))
TOP=$(dirname ${HERE})
cd ${TOP}

if [[ -v GIT_CLONE ]]
then
  git --version || apt-get -qq -y install git

  git clone \
    --depth=1 \
    https://github.com/adshares/aduser.git \
    --branch ${BUILD_BRANCH:-master} \
    ${BUILD_PATH}/build

  cd ${BUILD_PATH}/build
fi

if [[ ${BUILD_DATA_SERVICES_ONLY:-0} -eq 1 ]]
then
    cd aduser_data_services
fi

if [[ ${ADUSER_APP_ENV} == 'dev' ]]
then
    pipenv install --dev pipenv
elif [[ ${ADUSER_APP_ENV} == 'deploy' ]]
then
    pipenv install --deploy pipenv
else
    pipenv install pipenv
fi
