import os
import re
from datetime import timedelta

#: Twisted TCP port number
SERVER_PORT = int(os.getenv('ADUSER_PORT'))

#: Secret used for creating a tracking id
SECRET = os.getenv('ADUSER_TRACKING_SECRET')

#: Cookie key
COOKIE_NAME = bytes(os.getenv('ADUSER_COOKIE_NAME'))

#: Expiry period
config_period = re.match('^(\d+)(\w)$', os.getenv('ADUSER_EXPIRY_PERIOD'))

if not config_period:
    EXPIRY_PERIOD = timedelta(weeks=4)
else:
    count = int(config_period.group(1))

    if config_period.group(2) == 'w':
        EXPIRY_PERIOD = timedelta(weeks=count)
    elif config_period.group(2) == 'd':
        EXPIRY_PERIOD = timedelta(days=count)

ADUSER_DATA_PROVIDER = os.getenv('ADUSER_DATA_PROVIDER')