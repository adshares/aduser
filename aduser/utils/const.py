import os
import re
from datetime import timedelta

#: Secret used for creating a tracking id.
#:
#: `Environmental variable override: ADUSER_TRACKING_SECRET`
COOKIE_SECRET = os.getenv('ADUSER_TRACKING_SECRET', 'ChangeMe!')

#: Name of the cookie used for tracking.
#:
#: `Environmental variable override: ADUSER_COOKIE_NAME`
COOKIE_NAME = bytes(os.getenv('ADUSER_COOKIE_NAME', 'AdsharesAdUserTracking'))

#: Tracking cookie expiry period. The enviromental variable accepts 'w' for weeks and 'd' for days. Format: {num}{format}, eg. '`4w`' for 4 weeks.
#:
#: `Environmental variable override: ADUSER_EXPIRY_PERIOD`
COOKIE_EXPIRY_PERIOD = timedelta(weeks=4)
config_period = re.match('^(\d+)(\w)$', os.getenv('ADUSER_COOKIE_EXPIRY_PERIOD', '4w'))

if config_period:
    count = int(config_period.group(1))

    if config_period.group(2) == 'w':
        COOKIE_EXPIRY_PERIOD = timedelta(weeks=count)
    elif config_period.group(2) == 'd':
        COOKIE_EXPIRY_PERIOD = timedelta(days=count)

#: Logging config override JSON file.
#:
#: `Environmental variable override: ADUSER_LOG_CONFIG_JSON_FILE`
LOG_CONFIG_JSON_FILE = os.getenv('ADUSER_LOG_CONFIG_JSON_FILE', None)

#: Log level names must be Python logging level names.
#:
#: `Environmental variable override: ADUSER_LOG_LEVEL`
LOG_LEVEL = os.getenv('ADUSER_LOG_LEVEL', 'DEBUG').upper()
