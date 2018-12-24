import os

from aduser.plugins.simple import const as const_simple

MOCK_DATA_PATH = os.getenv('ADUSER_MOCK_DATA_PATH', '/var/www/aduser_data/mock.json')
BROWSCAP_CSV_PATH = const_simple.BROWSCAP_CSV_PATH
GEOLITE_PATH = const_simple.GEOLITE_PATH
PICKLE_BROWSCAP = const_simple.PICKLE_BROWSCAP
