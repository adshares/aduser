import json
import logging.config
import os

from twisted.internet import reactor

from aduser import server_utils

if __name__ == "__main__":

    # Configure logging
    logging.basicConfig()
    logfile_path = os.getenv('ADUSER_LOG_CONFIG_FILE')
    with open(logfile_path, "r") as fd:
        logging.config.dictConfig(json.load(fd))

    # Set up server
    server_utils.configure_server()

    # Run Twisted reactor
    reactor.run()
