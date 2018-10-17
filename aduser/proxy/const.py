import os
import re
from datetime import timedelta
from dotenv import load_dotenv

from aduser.simple_provider.client import SimpleProviderClient

load_dotenv()

#: Twisted TCP port number
SERVER_PORT = int(os.getenv('ADUSER_PORT'))

#: MongoDB instance port
MONGO_DB_PORT = int(os.getenv('ADUSER_MONGO_DB_PORT'))

#: MONGO DB Name
MONGO_DB_NAME = os.getenv('ADUSER_MONGO_DB_NAME')

#: Secret used for creating a tracking id
SECRET = os.getenv('ADUSER_TRACKING_SECRET')

#: Data provider redirect
DATA_PROVIDER_CLIENT = SimpleProviderClient()

#: Cookie key
COOKIE_NAME = bytes(os.getenv('ADUSER_COOKIE_NAME'))

#: Expiry period
config_period = re.match('^(\d+)(\w)$', os.getenv('ADUSER_EXPIRY_PERIOD'))

if not config_period:
    EXPIRY_PERIOD = timedelta(weeks=4)
else:
    count=int(config_period.group(1))

    if config_period.group(2) == 'w':
        EXPIRY_PERIOD = timedelta(weeks=count)
    elif config_period.group(2) == 'd':
        EXPIRY_PERIOD = timedelta(days=count)
