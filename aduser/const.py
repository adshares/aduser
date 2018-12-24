import os
import re
from datetime import timedelta

#: Twisted TCP port number
SERVER_PORT = int(os.getenv('ADUSER_PORT', 9090))

#: Secret used for creating a tracking id
SECRET = os.getenv('ADUSER_TRACKING_SECRET', 'ChangeMe!')

#: Secret used for creating a tracking id
PIXEL_PATH = os.getenv('ADUSER_PIXEL_PATH', 'path_to_pixel')

#: Cookie key
COOKIE_NAME = bytes(os.getenv('ADUSER_COOKIE_NAME', 'ChangeMe'))

#: Name of AdUser data backend plugin
DATA_PROVIDER = os.getenv('ADUSER_DATA_PROVIDER', 'aduser.plugins.examples.example')

#: Expiry period, accepts 'w' for weeks and 'd' for days. Format: {num}{format}, eg. 4w for 4 weeks.
config_period = re.match('^(\d+)(\w)$', os.getenv('ADUSER_EXPIRY_PERIOD', '4w'))

if not config_period:
    EXPIRY_PERIOD = timedelta(weeks=4)
else:
    count = int(config_period.group(1))

    if config_period.group(2) == 'w':
        EXPIRY_PERIOD = timedelta(weeks=count)
    elif config_period.group(2) == 'd':
        EXPIRY_PERIOD = timedelta(days=count)
