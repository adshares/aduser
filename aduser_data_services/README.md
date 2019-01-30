# AdUser Data Services

AdUser Data Services are Unix Socket Servers, which function as a data backend to AdUser.

Currently, there are two services:
1) Browscap - provides data identified through browser's user agent. Powered by [Browscap](https://browscap.org/).
2) Geolite - provides data identified through IP address. Powered by [MaxMind's GeoIP2](https://dev.maxmind.com/geoip/geoip2/geolite2/).

## Build
AdUser Data Services are fully implemented in python.

### Dependencies

All dependencies are in Pipfile, which is managed by [Pipenv](https://pipenv.readthedocs.io/en/latest/).

Ubuntu 18.04 dependencies can be found in [pre-build](scripts/pre-build.sh) and [pre-install](scripts/pre-install.sh) scripts.

## TL;DR  
```
git clone https://github.com/adshares/aduser
cd aduser/aduser_data_services
bash scripts/pre-build.sh
bash scripts/pre-install.sh
pipenv install
pipenv run daemon
```
