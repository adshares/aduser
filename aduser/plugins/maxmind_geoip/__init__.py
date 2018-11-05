import logging
import os

from twisted.internet import defer

from geoip import open_database

plugin_name = 'GeoIP_MaxMind'

db = None
mmdb_path = os.path.join(os.getenv('ADUSER_DATA_PATH'), 'GeoLite2-City.mmdb')
data_url = "http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz"
logger = logging.getLogger(__name__)


@defer.inlineCallbacks
def init():
    global db

    if not db:
        logger.info("Initializing GeoIP database.")
        yield update_source()
        if db:
            logger.info("GeoIP database initialized.")


def update_user(user):
    global db

    if db and user['client_ip']:

        match = db.lookup(user['client_ip'])
        if match:
            user_data = match.to_dict()
            user_data['subdivisions'] = list(user_data['subdivisions'])
            user['keywords'].update(user_data)
        else:
            logger.warning("IP not found in GeoIP db.")


@defer.inlineCallbacks
def update_source():
    global db

    if os.path.exists(mmdb_path):
        logger.info("Updating GeoIP database.")
        db = yield open_database(mmdb_path)
        if db:
            logger.info("GeoIP database updated.")
    else:
        logger.error("GeoIP database not found.")


def schema():
    schema_data = {'name': 'maxmind_geoip',
                   'version': 0.1,
                   'children': [
                       {'label': 'timezone',
                        'key': 'timezone',
                        'values': []},
                       {'label': 'subdivisions',
                        'key': 'subdivisions',
                        'values': []},
                       {'label': 'country',
                        'key': 'country',
                        'values': []}
                        ]
                   }

    return schema_data
#     'timezone': 'Europe/Warsaw',
#     'subdivisions': frozenset(['MZ']),
#     'location': (52.25, 21.0),
#     'country': 'PL',
#     'ip': '176.221.114.114',
#     'continent': 'EU'
