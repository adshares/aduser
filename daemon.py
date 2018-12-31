import json
import logging.config
import os

from twisted.internet import reactor

from aduser import server_utils
from aduser import db

if __name__ == "__main__":

    # Configure logging
    logging.basicConfig()
    logfile_path = os.getenv('ADUSER_LOG_CONFIG_FILE', 'config/log_config.json')
    with open(logfile_path, "r") as fd:
        logging.config.dictConfig(json.load(fd))

    db.configure_db()

    # Set up server
    server_utils.configure_server()

    # Run Twisted reactor
    reactor.run()
