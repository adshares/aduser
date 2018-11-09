#!/usr/bin/env bash

set -e

# Ubuntu 18.04 only

# Install dependencies for python operations
apt-get -qq -y install python python-pip

pip install pipenv

CWD=`pwd`
mkdir -p ${ADUSER_DATA_PATH}
TEMP_DIR=`mktemp -d`

cd $TEMP_DIR
python -c "from urllib import urlretrieve; \
           urlretrieve('http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz', \
           '$TEMP_DIR/GeoLite2-City.tar.gz')"

python -c "import tarfile; \
           import shutil; \
           tar = tarfile.open('$TEMP_DIR/GeoLite2-City.tar.gz', 'r:gz'); \
           version = sorted(tar.getnames())[0]; \
           mmdb_file = tar.getmember(version + '/GeoLite2-City.mmdb'); \
           tar.extract(mmdb_file); \
           tar.close(); \
           shutil.move(version + '/GeoLite2-City.mmdb', '$ADUSER_DATA_PATH/GeoLite2-City.mmdb')"

cd  $CWD
rm -r $TEMP_DIR
