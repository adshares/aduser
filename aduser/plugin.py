from __future__ import print_function

import importlib

from aduser import const

#: Attribute where the data plugin is instantiated.
data = None


def initialize():
    """
    Initialize the plugin by name.
    Searches for a package with the same name in ADUSER_PLUGINS_PATH and imports it.
    Allows access via `data` attribute.

    :return:
    """
    global data

    data = importlib.import_module(const.ADUSER_DATA_PROVIDER)
    data.init()
