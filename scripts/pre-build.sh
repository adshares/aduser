#!/usr/bin/env bash

set -e

# Ubuntu 18.04 only

# Install dependencies for python operations
apt-get -qq -y install python python-pip

pip install pipenv

## Install pybrowscap
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

pipenv run install_geoip
