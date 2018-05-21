import os
from base64 import b64decode
from datetime import timedelta
from aduser.simple_provider.server.data_sources.browsecap import BrowsCapSource
from aduser.simple_provider.server.data_sources.maxmind_geoip import GeoIpSource

#: Twisted TCP port number
SERVER_PORT = 8082

#: Secret used for creating a tracking id
SECRET = "NotVerySecret_Provider"

#: Blank pixel data
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")

#: Cookie key
COOKIE_NAME = b"aduser_simple_provider_tid"

#: Cookie key
REQUEST_COOKIE_NAME = b"aduser_proxy_tid"

#: Expiry period
EXPIRY_PERIOD = timedelta(weeks=4)

#: MongoDB instance port
MONGO_DB_PORT = 27017

#: MONGO DB Name
MONGO_DB_NAME = 'aduser_simple_provider'

#: User DataSource classes
USER_DATA_SOURCES = [BrowsCapSource(os.path.join(os.environ["ADUSER_ROOT"], 'aduser', 'data', 'browsercap.csv')),
                     GeoIpSource(os.path.join(os.environ["ADUSER_ROOT"], 'aduser', 'data', 'GeoLite2-City.mmdb'))]
