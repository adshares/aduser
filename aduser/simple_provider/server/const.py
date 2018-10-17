import os
import re
from base64 import b64decode
from datetime import timedelta
from dotenv import load_dotenv
#from aduser.simple_provider.server.data_sources.browsecap import BrowsCapSource
#from aduser.simple_provider.server.data_sources.maxmind_geoip import GeoIpSource

load_dotenv()

#: Twisted TCP port number
SERVER_PORT = int(os.getenv('ADUSER_SIMPLE_SERVER_PORT'))

#: Secret used for creating a tracking id
SECRET = os.getenv('ADUSER_SIMPLE_SERVER_SECRET')

#: Blank pixel data
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")

#: Cookie key
COOKIE_NAME = bytes(os.getenv('ADUSER_SIMPLE_SERVER_COOKIE_NAME'))

#: Cookie key
REQUEST_COOKIE_NAME = bytes(os.getenv('ADUSER_SIMPLE_SERVER_REQUEST_COOKIE_NAME'))

#: Expiry period
config_period = re.match('^(\d+)(\w)$', os.getenv('ADUSER_SIMPLE_SERVER_EXPIRY_PERIOD'))

if not config_period:
    EXPIRY_PERIOD = timedelta(weeks=4)
else:
    count=int(config_period.group(1))

    if config_period.group(2) == 'w':
        EXPIRY_PERIOD = timedelta(weeks=count)
    elif config_period.group(2) == 'd':
        EXPIRY_PERIOD = timedelta(days=count)


#: MongoDB instance port
MONGO_DB_PORT = os.getenv('ADUSER_SIMPLE_SERVER_MONGO_DB_PORT')

#: MONGO DB Name
MONGO_DB_NAME = os.getenv('ADUSER_SIMPLE_SERVER_MONGO_DB_NAME')

#: User DataSource classes
#USER_DATA_SOURCES = [BrowsCapSource(os.path.join(os.environ["ADUSER_ROOT"], 'aduser', 'data', 'browsercap.csv')),
#                     GeoIpSource(os.path.join(os.environ["ADUSER_ROOT"], 'aduser', 'data', 'GeoLite2-City.mmdb'))]

USER_DATA_SOURCES = []
