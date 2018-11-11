from __future__ import print_function

import glob
import imp
import os
from os.path import basename, dirname

data = None


def initialize(name):

    global data

    plugin_files = glob.glob(os.path.join(os.getenv('ADUSER_PLUGINS_PATH'), "*"))
    for p in plugin_files:
        if os.path.isdir(p) and basename(p) == name:
            possible_module_info = imp.find_module(basename(p), [dirname(p)])
            possible_plugin = imp.load_module(os.path.dirname(p), *possible_module_info)

            data = possible_plugin
            data.init()

            break
