<p align="center">
    <a href="https://adshares.net/" title="Adshares sp. z o.o." target="_blank">
        <img src="https://adshares.net/logos/ads.svg" alt="Adshares" width="100" height="100">
    </a>
</p>
<h3 align="center"><small>Adshares / AdUser</small></h3>
<p align="center">
    <a href="https://github.com/adshares/aduser/issues/new?template=bug_report.md&labels=Bug">Report bug</a>
    ·
    <a href="https://github.com/adshares/aduser/issues/new?template=feature_request.md&labels=New%20Feature">Request feature</a>
    ·
    <a href="https://github.com/adshares/aduser/wiki">Wiki</a>
</p>
<p align="center">
    <a href="https://travis-ci.org/adshares/aduser" title="Build Status" target="_blank">
        <img src="https://travis-ci.org/adshares/aduser.svg?branch=master" alt="Build Status">
    </a>
    <a href="https://sonarcloud.io/dashboard?id=adshares-aduser" title="Code Quality" target="_blank">
        <img src="https://sonarcloud.io/api/project_badges/measure?project=adshares-aduser&metric=alert_status" alt="Code Quality">
    </a>
    <a href="http://adshares-aduser.readthedocs.io/en/latest" title="Docs Status" target="_blank">
        <img src="https://readthedocs.org/projects/adshares-aduser/badge/?version=latest" alt="Docs Status">
    </a>
</p>

AdUser is a back-end service for determining impression context.
It accepts requests from [AdServer](https://github.com/adshares/adserver).

## Quick Start (on Ubuntu 18.04 LTS)

Install dependencies
```bash
apt-get -y install --no-install-recommends python python-pip python-dev gcc
pip install pipenv
```

Clone and run
```bash
git clone https://github.com/adshares/aduser.git
cd aduser
pipenv install
pipenv run python daemon.py
```

## More Info

- [Documentation](https://adshares-aduser.readthedocs.io/en/latest)
- [Authors](https://github.com/adshares/aduser/contributors)
- Available [Versions](https://github.com/adshares/aduser/tags) (we use [Semantic Versioning](http://semver.org/))

### Related projects

- [AdServer](https://github.com/adshares/adserver) - the core logic behind it all

### License

This work is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This work is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
[GNU General Public License](LICENSE) for more details.

You should have received a copy of the License along with this work.
If not, see <https://www.gnu.org/licenses/gpl.html>.
