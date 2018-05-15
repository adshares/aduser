from aduser.simple_provider.client import SimpleProviderClient
from datetime import timedelta

#: Twisted TCP port number
SERVER_PORT = 8081

#: MongoDB instance port
MONGO_DB_PORT = 27017

#: MONGO DB Name
MONGO_DB_NAME = 'aduser_cache'

#: Secret used for creating a tracking id
SECRET = "NotVerySecret_Proxy"

#: Data provider redirect
DATA_PROVIDER_CLIENT = SimpleProviderClient()

#: Cookie key
COOKIE_NAME = b"aduser_proxy_tid"

#: Expiry period
EXPIRY_PERIOD = timedelta(weeks=4)
