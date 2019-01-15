import os
import re
from datetime import timedelta

#: Twisted TCP port number, ie. AdUser server port
#:
#: `Environmental variable override: ADUSER_PORT`
SERVER_PORT = int(os.getenv('ADUSER_PORT', 9090))

#: Secret used for creating a tracking id.
#:
#: `Environmental variable override: ADUSER_TRACKING_SECRET`
SECRET = os.getenv('ADUSER_TRACKING_SECRET', 'ChangeMe!')

#: Configurable path to pixel. Becomes start of pixel request paths returned by getPixelPath.
#:
#: `Environmental variable override: ADUSER_PIXEL_PATH`
PIXEL_PATH = os.getenv('ADUSER_PIXEL_PATH', 'path_to_pixel')

#: Name of the cookie used for tracking.
#:
#: `Environmental variable override: ADUSER_COOKIE_NAME`
COOKIE_NAME = bytes(os.getenv('ADUSER_COOKIE_NAME', 'AdsharesAdUserTracking'))

#: Name of AdUser data backend plugin.
#:
#: `Environmental variable override: ADUSER_DATA_PROVIDER`
DATA_PROVIDER = os.getenv('ADUSER_DATA_PROVIDER', 'aduser.plugins.examples.example')

#: Tracking cookie expiry period. The enviromental variable accepts 'w' for weeks and 'd' for days. Format: {num}{format}, eg. '`4w`' for 4 weeks.
#:
#: `Environmental variable override: ADUSER_EXPIRY_PERIOD`
EXPIRY_PERIOD = timedelta(weeks=4)
config_period = re.match('^(\d+)(\w)$', os.getenv('ADUSER_EXPIRY_PERIOD', '4w'))

if config_period:
    count = int(config_period.group(1))

    if config_period.group(2) == 'w':
        EXPIRY_PERIOD = timedelta(weeks=count)
    elif config_period.group(2) == 'd':
        EXPIRY_PERIOD = timedelta(days=count)

NO_CACHE = bool(int(os.getenv('ADUSER_NO_CACHE', 1)))
