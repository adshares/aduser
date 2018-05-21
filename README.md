# AdUser
[![Docs Status](https://readthedocs.org/projects/adshares-aduser/badge/?version=latest)](http://adshares-aduser.readthedocs.io/en/latest/)
## Build
AdUser is fully implemented in python.

### Dependencies

All dependencies are listed in requirements.txt file.

#### Linux

Example for Debian based systems:
```
$ sudo apt-get install python-virtualenv mongodb python-pip virtualenv
```

Create virtualenv environment for aduser.
```
$ cd ~
$ virtualenv aduser
$ source ~/aduser/bin/activate

$ export VIRTUALENV_ROOT=$HOME/aduser
$ export PYTHONPATH=$HOME/aduser:$PYTHONPATH
```

Create folder for MONGO database.
```
$ mkdir -p ~/aduser/db/mongo
```


Create folders for supervisor.
```
$ mkdir -p ~/aduser/log
$ mkdir -p ~/aduser/run/supervisor ~/aduser/run/aduser ~/aduser/run/mongo
```

Download source code and install dependencies.
```
$ git clone https://github.com/adshares/aduser.git ~/aduser/aduser
$ pip install -r ~/aduser/aduser/requirements.txt
```

Run aduser daemon.
```
$ supervisord -c ~/aduser/aduser/config/supervisord.conf
```

## Build
```
$ cd ~/aduser/aduser
$ trial db iface stats
```
## TL;DR  
```
#aduser
apt-get install python-virtualenv mongodb python-pip virtualenv
screen -S aduser
cd ~
virtualenv aduser
export VIRTUALENV_ROOT=$HOME/aduser
export PYTHONPATH=$HOME/aduser:$PYTHONPATH
source ./aduser/bin/activate
mkdir -p ./aduser/db/mongo
mkdir -p ./aduser/log
mkdir -p ./aduser/run/supervisor ./aduser/run/aduser ./aduser/run/mongo
git clone https://github.com/adshares/aduser.git ./aduser/aduser
pip install -r ./aduser/aduser/requirements.txt
supervisord -c ./aduser/aduser/config/supervisord.conf
```
