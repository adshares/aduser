from base64 import b64decode

#: taxonomy name and identifier
taxonomy_name = 'example_skeleton'
#: taxonomy version
taxonomy_version = '0.0.1'

#: Default taxonomy
taxonomy = {'meta': {'name': taxonomy_name,
                     'version': taxonomy_version},
            'data': []}

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


def update_data(data, request):
    """
    Update data object with plugin data.

    :param data: Data object with default data.
    :param request: Instance of `twisted.web.http.Request`
    :return: Updated data object.
    """
    return data
