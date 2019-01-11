#!/usr/bin/env bash

set -e

env | sort

if [ ! -v PIPENV_DONT_LOAD_ENV ]; then
    envsubst < .env.dist | tee .env
fi

if [ ${ADUSER_APP_ENV} == 'dev' ]; then
    pipenv install --dev pipenv
elif [ ${ADUSER_APP_ENV} == 'deploy' ]; then
    pipenv install --deploy pipenv
else
    pipenv install pipenv
fi
