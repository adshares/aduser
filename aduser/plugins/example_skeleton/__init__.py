from base64 import b64decode

#: Schema name and identifier
schema_name = 'example_skeleton'
#: Schema version
schema_version = '0.0.1'

#: Default schema
schema = {'meta': {'name': schema_name,
                   'ver': schema_version},
          'values': {}}

#: Pixel value (1x1 GIF)
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")


def pixel(request):
    """
    Return a valid pixel.

    :param request: Instance of `twisted.web.http.Request`.
    :return:
    """
    request.setHeader(b"content-type", b"image/gif")
    return PIXEL_GIF


def init():
    """
    Initialize this plugin.

    :return:
    """
    pass


def update_data(data, request):
    """
    Update data object with plugin data.

    :param data: Data object with default data.
    :param request: Instance of `twisted.web.http.Request`
    :return: Updated data object.
    """
    return data
