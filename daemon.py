import logging

from twisted.internet import reactor

from aduser import data, db
from aduser.iface import const as iface_const, server as iface_server
from aduser.utils import const as utils_const, logs
from aduser.data import const as data_const
from aduser.data.recaptcha import const as recaptcha_const

if __name__ == "__main__":

    # Set up logging.
    logs.setup()

    # Configuring database.
    db.configure_db()

    # Initialize plugin.
    data.configure_plugin()

    # Set up server
    iface_server.configure_server()

    # Configure logger.
    logger = logging.getLogger(__name__)

    logger.info("Initializing AdUser server on port {0}.".format(iface_const.SERVER_PORT))
    logger.info("Data provider: {0}".format(data_const.DATA_PROVIDER))
    logger.info("Tracking cookie name: {0}".format(utils_const.COOKIE_NAME))
    logger.info("Tracking cookie expiration: {0}".format(utils_const.COOKIE_EXPIRY_PERIOD))
    logger.info("Pixel path: {0}".format(iface_const.PIXEL_PATH))
    logger.info("Score path: {0}".format(iface_const.SCORE_PATH))
    logger.info("reCaptcha: {0}".format(recaptcha_const.RECAPTCHA_SITE_KEY or 'off'))

    logger.info("AdUser ready and listening.")

    # Run Twisted reactor
    reactor.run()
