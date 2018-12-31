# AdUser
[![Build Status](https://travis-ci.org/adshares/aduser.svg?branch=master)](https://travis-ci.org/adshares/aduser)
[![Build Status](https://sonarcloud.io/api/project_badges/measure?project=adshares-aduser&metric=alert_status)](https://sonarcloud.io/dashboard?id=adshares-aduser)
[![Docs Status](https://readthedocs.org/projects/adshares-aduser/badge/?version=latest)](http://adshares-aduser.readthedocs.io/en/latest/)
## Build
AdUser is fully implemented in python.

### Dependencies

All dependencies are in Pipfile, which is managed by [Pipenv](https://pipenv.readthedocs.io/en/latest/).

Ubuntu 18.04 dependencies can be found in [pre-build](scripts/pre-build.sh) and [pre-install](scripts/pre-install.sh) scripts.

### Installation

Installation instructions can be found in the [documentation](https://adshares-aduser.readthedocs.io/en/latest/).

Please note, that you'll want to configure AdUser. Read the [configuration documentation](https://adshares-aduser.readthedocs.io/en/latest/config.html).

## TL;DR  
```
bash scripts/pre-build.sh
bash scripts/pre-install.sh
pipenv install
pipenv run daemon
```
