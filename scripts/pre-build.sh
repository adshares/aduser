#!/usr/bin/env bash

set -e

# Ubuntu 18.04 only
apt-get update

# Install dependencies for python operations
apt-get -qq -y install --no-install-recommends python python-pip python-dev libc6-dev

pip install pipenv

if [ -v BUILD_WITH_PYBROWSCAP ]; then

    ## Install pybrowscap
    echo "Installing pybrowscap"
    CWD=`pwd`
    TEMP_DIR=`mktemp -d`

    cd $TEMP_DIR
    python -c "from urllib import urlretrieve; \
               urlretrieve('https://github.com/char0n/pybrowscap/archive/master.zip', \
               '$TEMP_DIR/pybrowscap.zip')"

    python -m zipfile -e $TEMP_DIR/pybrowscap.zip $TEMP_DIR/
    mv -n $TEMP_DIR/pybrowscap-master/pybrowscap $BUILD_PATH/

    cd $CWD
    rm -r $TEMP_DIR
    echo "Pybrowscap installed to: $BUILD_PATH/pybrowscap"

fi

if [ -v BUILD_WITH_GEOIP ]; then
    pipenv run install_geoip
fi
