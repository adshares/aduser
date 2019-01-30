from base64 import b64decode

#: This is a skeleton data provider, you can use it to extend or create new functionality for your data backend.
#: Your module must implement the following:
#: `taxonomy` attribute must return a dictionary
#: `pixel(request)` method must take twisted.web.http.Request as parameter and return a valid response (eg. GIF or redirect).
#: `update_data(data, request)` method must take user data dictionary and twisted.web.http.Request. It should return updated user data.

#: Pixel value (1x1 GIF)
_PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")

#: Default taxonomy
taxonomy = {'meta': {'name': 'example_skeleton',
                     'version': '0.0.1'},
            'data': []}


def pixel(request):
    """
    Return a valid pixel.

    :param request: Instance of `twisted.web.http.Request`.
    :return: Pixel value
    """
    request.setHeader(b"content-type", b"image/gif")
    return _PIXEL_GIF


def update_data(data, request):
    """
    Update data object with plugin data.

    :param data: Data object with default data.
    :param request: Instance of `twisted.web.http.Request`
    :return: Updated data object.
    """
    return data
