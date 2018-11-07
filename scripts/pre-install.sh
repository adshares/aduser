#!/usr/bin/env bash

set -e

# Ubuntu 18.04 only

# Install dependencies for python operations
apt-get install -y python python-pip

pip install pipenv
