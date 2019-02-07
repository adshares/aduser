import os

#: Twisted TCP port number, ie. AdUser server port
#:
#: `Environmental variable override: ADUSER_PORT`
SERVER_PORT = int(os.getenv('ADUSER_PORT', 8010))

#: Configurable path to pixel. Becomes start of pixel request paths returned by getPixelPath.
#:
#: `Environmental variable override: ADUSER_PIXEL_PATH`
PIXEL_PATH = os.getenv('ADUSER_PIXEL_PATH', 'path_to_pixel')

#: Enable or disable request cache. 1 for cache disabled, 0 for enabled.
#:
#: `Environmental variable override: ADUSER_DEBUG_WITHOUT_CACHE`
DEBUG_WITHOUT_CACHE = bool(int(os.getenv('ADUSER_DEBUG_WITHOUT_CACHE', 0)))
