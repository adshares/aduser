import os

BROWSCAP_CSV_PATH = os.getenv('ADUSER_BROWSCAP_CSV_PATH', '/var/www/aduser_data/browscap.csv')
GEOLITE_PATH = os.getenv('ADUSER_GEOLITE_PATH', '/var/www/aduser_data/GeoLite2-City.mmdb')
PICKLE_BROWSCAP = os.getenv('PICKLE_PYBROWSCAP', False)
