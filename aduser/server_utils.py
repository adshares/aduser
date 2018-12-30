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
    logger.info("Initializing AdUser server on port {0}.".format(const.SERVER_PORT))
    logger.info("Tracking cookie name: {0}".format(const.COOKIE_NAME))
    logger.info("Tracking cookie expiration: {0}".format(const.EXPIRY_PERIOD))
    logger.info("Pixel path: {0}".format(const.PIXEL_PATH))

    plugin.initialize()

    # Initialize the data plugin.
    if not plugin.data:
        logger.error("Failed to load data plugin, exiting.")
        sys.exit(1)

    logger.info("AdUser ready and listening.")

    return reactor.listenTCP(const.SERVER_PORT, Site(root))
