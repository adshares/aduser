#!/usr/bin/env bash

# Ubuntu 18.04 only

# Install dependencies for python operations
apt-get install -y python python-pip

pip install pipenv

## Install pybrowscap

TEMP_DIR=`mktemp -d`

python -c "from urllib import urlretrieve; \
           urlretrieve('https://github.com/char0n/pybrowscap/archive/master.zip', \
           '$TEMP_DIR/pybrowscap.zip')"

python -m zipfile -e $TEMP_DIR/pybrowscap.zip $TEMP_DIR

pipenv run python $TEMP_DIR/pybrowscap-master/setup.py install

## Cleanup
# Cleanup easyinstall

rm -r dist
rm -r build
rm -r pybrowscap.egg-info

rm -r $TEMP_DIR
