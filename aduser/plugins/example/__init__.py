import logging
import random
from base64 import b64decode

logger = logging.getLogger(__name__)
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")
taxonomy = {}

taxonomy_name = 'example'
taxonomy_version = '0.0.1'


def init():
    logger.info("Initializing example data plugin.")
    generate_taxonomy()


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
    global taxonomy

    input_choices = ['Professor X', 'Deadpool', 'Aquaman', 'professorX']

    data['keywords'].update({'0001': random.choice(taxonomy['values']['0001']['values'])['key']})
    data['keywords'].update({'0002': random.randint(0, 100)})
    data['keywords'].update({'0003': random.choice(input_choices)})
    data['keywords'].update({'0004': bool(random.getrandbits(1))})
    # data['keywords'] = normalize(data['keywords'])

    data['human_score'] = random.random()
    return data


def generate_taxonomy():
    global taxonomy
    global taxonomy_name
    global taxonomy_version

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

    taxonomy = {'meta': {'name': taxonomy_name,
                         'ver': taxonomy_version},
              'values': values}
#
#
# def normalize(data):
#     for key in data:
#         if taxonomy['values'][key]['type'] == 'num':
#             data[key] = int(data[key])
#         elif taxonomy['values'][key]['type'] == 'input':
#             data[key] = data[key].replace(' ', '').replace('_', '').lower()
#         elif taxonomy['values'][key]['type'] == 'bool':
#             data[key] = bool(data[key])
#     return data
