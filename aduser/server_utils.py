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
    logger.info("Tracking cookie name: '{0}'".format(const.COOKIE_NAME))
    logger.info("Tracking cookie expiration: {0}.".format(const.EXPIRY_PERIOD))
    logger.info("Pixel path: {0}.".format(const.PIXEL_PATH))

    # Initialize the data plugin.
    plugin.initialize(const.ADUSER_DATA_PROVIDER)

    if not plugin.data:
        logger.info("Failed to load data plugin, exiting.")
        sys.exit(1)

    return reactor.listenTCP(const.SERVER_PORT, Site(root))
