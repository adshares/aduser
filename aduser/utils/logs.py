import json
import logging
import os
from aduser.utils import const as utils_const


def setup():
    """
    Configure global logging configuration
    1. Set up log level (DEBUG by default).
    2. Set up default message format.
    3. (optional) override the settings.

    :return:
    """
    if hasattr(logging, utils_const.LOG_LEVEL):
        loglevel = getattr(logging, utils_const.LOG_LEVEL)
    else:
        loglevel = logging.DEBUG

    # Default logging config
    logging.basicConfig(format='[%(asctime)s] %(name)-20s %(levelname)-9s %(message)s',
                        datefmt="%Y-%m-%dT%H:%M:%SZ",
                        handlers=[logging.StreamHandler()],
                        level=loglevel)

    # Override logging config if provided
    logfile_path = utils_const.LOG_CONFIG_JSON_FILE
    if logfile_path and os.path.exists(logfile_path):
        with open(logfile_path, "r") as fd:
            log_config = json.load(fd)

        logging.config.dictConfig(log_config)
