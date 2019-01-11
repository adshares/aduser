#!/usr/bin/env bash

set -e

env | sort

if [[ ${ADUSER_APP_ENV:-dev} == "dev" ]]; then
    pipenv install --dev pipenv
elif [[ ${ADUSER_APP_ENV} == "deploy" ]]; then
    pipenv install --deploy pipenv
else
    pipenv install pipenv
fi
