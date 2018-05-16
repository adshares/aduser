import logging.config
import json
import os

from twisted.internet import reactor
from aduser.simple_provider import server as provider_server
from aduser.proxy import server as proxy_server


if __name__ == "__main__":

    logging.basicConfig()

    logfile_path = os.path.join(os.environ["ADUSER_ROOT"], "aduser", "config", "log_config.json")

    with open(logfile_path, "r") as fd:
        logging.config.dictConfig(json.load(fd))

    provider_server.configure_server()
    proxy_server.configure_server()

    reactor.run()
