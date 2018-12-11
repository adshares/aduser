import os

#: MongoDB port
MONGO_DB_PORT = int(os.getenv('ADUSER_MONGO_DB_PORT', '27017'))
MONGO_DB_NAME = os.getenv('ADUSER_MONGO_DB_NAME', 'aduser')
MONGO_DB_HOST = os.getenv('ADUSER_MONGO_DB_HOST', 'localhost')
