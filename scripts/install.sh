#!/usr/bin/env bash

set -e

# Create directories
mkdir -p ${INSTALLATION_PATH}

if [[ ${INSTALL_DATA_SERVICES_ONLY:-0} -eq 1 ]]; then
    cd aduser_data_services
fi

mv Pipfile ${INSTALLATION_PATH}/
mv Pipfile.lock ${INSTALLATION_PATH}/

mv .venv ${INSTALLATION_PATH}/

if [[ ${INSTALL_DATA_SERVICES_ONLY:-0} -eq 1 ]]; then
    mv aduser_data_services ${INSTALLATION_PATH}/
else
    mv aduser ${INSTALLATION_PATH}/
    mv daemon.py ${INSTALLATION_PATH}/
fi

cd ${INSTALLATION_PATH}/

if [[ ${INSTALL_GEOLITE_DATA:-0} -eq 1 ]]; then

    echo "Downloading geolite data"
    CWD=`pwd`
    mkdir -p ${INSTALL_DATA_PATH}
    TEMP_DIR=`mktemp -d`
    cd ${TEMP_DIR}
    pipenv run python -c "from urllib import urlretrieve; \
                          urlretrieve('http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz', \
                          '${TEMP_DIR}/GeoLite2-City.tar.gz'); \
                          \
                          import tarfile; \
                          tar = tarfile.open('${TEMP_DIR}/GeoLite2-City.tar.gz', 'r:gz'); \
                          version = sorted(tar.getnames())[0]; \
                          mmdb_file = tar.getmember(version + '/GeoLite2-City.mmdb'); \
                          tar.extract(mmdb_file); \
                          tar.close(); \
                          \
                          import shutil; \
                          shutil.move(version + '/GeoLite2-City.mmdb', '${INSTALL_DATA_PATH}/GeoLite2-City.mmdb')"

    cd  ${CWD}
    rm -r ${TEMP_DIR}
    echo "Download finished"

fi

if [[ ${INSTALL_BROWSCAP_DATA:-0} -eq 1 ]]; then

    echo "Downloading browscap data (this make take a while). \
          You can find the dataset here: https://browscap.org/"
    mkdir -p ${INSTALL_DATA_PATH}
    pipenv run python -c "from pybrowscap.loader import Downloader; \
                          from pybrowscap.loader.csv import URL; \
                          Downloader(URL).get('${INSTALL_DATA_PATH}/browscap.csv')"
    echo "Download finished"

fi
