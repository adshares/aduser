from base64 import b64decode
from aduser.data import const as data_const

#: This is a skeleton data provider, you can use it to extend or create new functionality for your data backend.
#: Your module must implement the following:
#: `taxonomy` attribute must return a dictionary
#: `score(tracking_id, request)` method must take user tracking id and twisted.web.http.Request as parameter and return a valid response (eg. reCaptcha JS code) or None.
#: `score_data(tracking_id, token, request)` method must take user tracking id, token and twisted.web.http.Request as parameter and return a human score (float).
#: `pixel(tracking_id, request)` method must take user tracking id and twisted.web.http.Request as parameter and return a valid response (eg. GIF or redirect).
#: `update_data(data, request)` method must take user data dictionary and twisted.web.http.Request. It should return updated user data.

#: Pixel value (1x1 GIF)
_PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")

#: Default taxonomy
taxonomy = {'meta': {'name': 'example_skeleton',
                     'version': '0.0.1'},
            'data': []}


def score(tracking_id, request):
    """
    Return scoring JS or None.

    :param tracking_id: User tracking id.
    :param request: Instance of `twisted.web.http.Request`.
    :return: score JS
    """
    return None


def score_data(tracking_id, token, request):
    """
    Return human score.

    :param tracking_id: User tracking id.
    :param token: Token.
    :param request: Instance of `twisted.web.http.Request`.
    :return: human score
    """
    return data_const.DEFAULT_HUMAN_SCORE


def pixel(tracking_id, request):
    """
    Return a valid pixel.

    :param tracking_id: User tracking id.
    :param request: Instance of `twisted.web.http.Request`.
    :return: Pixel value
    """
    request.setHeader(b"content-type", b"image/gif")
    return _PIXEL_GIF


def update_data(data, request_data):
    """
    Update data object with plugin data.

    :param data: Data object with default data.
    :param request_data: Instance of `twisted.web.http.Request`
    :return: Updated data object.
    """
    return data
