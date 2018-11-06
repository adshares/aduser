from base64 import b64decode

schema_name = 'example_skeleton'
schema_version = '0.0.1'
schema = {'meta': {'name': schema_name,
                   'ver': schema_version},
          'values': {}}

PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")


def pixel(request):
    request.setHeader(b"content-type", b"image/gif")
    return PIXEL_GIF


def init():
    pass


def update_data(data, request):
    return data


def normalize(data):
    return data

