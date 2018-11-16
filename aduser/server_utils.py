import logging
import sys

from twisted.internet import reactor
from twisted.web.server import Site

from aduser import const, plugin
from aduser.api.v1 import configure_entrypoint


def configure_server():
    """
    Initialize the server.

    :return: An instance of a class implementing `twisted.internet.interfaces.IListeningPort`.
    """
    # Set up endpoints.
    root = configure_entrypoint()

    # Configure logger.
    logger = logging.getLogger(__name__)
    logger.info("Initializing server.")
    logger.info("Configured with cookie name: '{0}' with expiration of {1}.".format(const.COOKIE_NAME,
                                                                                    const.EXPIRY_PERIOD))
    # Initialize the data plugin.
    plugin.initialize(const.ADUSER_DATA_PROVIDER)

    if not plugin.data:
        logger.info("Failed to load data plugin, exiting.")
        sys.exit(1)

    return reactor.listenTCP(const.SERVER_PORT, Site(root))
