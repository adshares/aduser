import logging
import random
from base64 import b64decode

logger = logging.getLogger(__name__)
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")
schema = {}

schema_name = 'example'
schema_version = '0.0.1'


def init():
    logger.info("Initializing example data plugin.")
    generate_schema()


def pixel(request):
    """
    :return: pixel image
    """
    request.setHeader(b"content-type", b"image/gif")
    return PIXEL_GIF


# def pixel_with_redirect(request):
#     request.redirect("other_provider_url")
#     return ''


def update_data(data, request_data):
    # Ignore request_data
    global schema

    input_choices = ['Professor X', 'Deadpool', 'Aquaman', 'professorX']

    data['keywords'].update({'0001': random.choice(schema['values']['0001']['values'])['key']})
    data['keywords'].update({'0002': random.randint(0, 100)})
    data['keywords'].update({'0003': random.choice(input_choices)})
    data['keywords'].update({'0004': bool(random.getrandbits(1))})
    # data['keywords'] = normalize(data['keywords'])

    data['human_score'] = random.random()
    return data


def generate_schema():
    global schema
    global schema_name
    global schema_version

    values = {'0001': {'label': 'Interests',
                       'type': 'dict',
                       'values': [{'label': 'Interest: DC', 'key': '0001'},
                                  {'label': 'Interest: Marvel', 'key': '0002'},
                                  {'label': 'Interest: Marvel: Spiderman', 'key': '0003'},
                                  {'label': 'Interest: Marvel: Venom', 'key': '0004'}],
                       },
              '0002': {'label': 'Comic books owned',
                       'type': 'num'},
              '0003': {'label': 'Favourite superhero',
                       'type': 'input'},
              '0004': {'label': 'Registered at comicbookheroes.net',
                       'type': 'bool'}
              }

    schema = {'meta': {'name': schema_name,
                       'ver': schema_version},
              'values': values}
#
#
# def normalize(data):
#     for key in data:
#         if schema['values'][key]['type'] == 'num':
#             data[key] = int(data[key])
#         elif schema['values'][key]['type'] == 'input':
#             data[key] = data[key].replace(' ', '').replace('_', '').lower()
#         elif schema['values'][key]['type'] == 'bool':
#             data[key] = bool(data[key])
#     return data
