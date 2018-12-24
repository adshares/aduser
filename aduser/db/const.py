import os

#: MongoDB port, ie. database connection port for AdUser application.
#:
#: `Environmental variable override: ADUSER_MONGO_DB_PORT`
MONGO_DB_PORT = int(os.getenv('ADUSER_MONGO_DB_PORT', 27017))
#: MongoDB database name, ie. database name for AdUser application.
#:
#: `Environmental variable override: ADUSER_MONGO_DB_NAME`
MONGO_DB_NAME = os.getenv('ADUSER_MONGO_DB_NAME', 'aduser')
#: MongoDB database host, ie. database host for AdUser application.
#:
#: `Environmental variable override: ADUSER_MONGO_DB_HOST`
MONGO_DB_HOST = os.getenv('ADUSER_MONGO_DB_HOST', 'localhost')
