import os
import logging.config
import json


from twisted.internet import reactor

from aduser import server_utils

if __name__ == "__main__":

    logging.basicConfig()

    logfile_path = os.getenv('ADUSER_LOG_CONFIG_FILE')

    with open(logfile_path, "r") as fd:
        logging.config.dictConfig(json.load(fd))

    server_utils.configure_server()

    reactor.run()
