import logging
import random
from base64 import b64decode

logger = logging.getLogger(__name__)
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")

taxonomy = {}
taxonomy_name = 'example'
taxonomy_version = '0.0.1'


def pixel(request):
    """
    :return: pixel image
    """
    request.setHeader(b"content-type", b"image/gif")
    return PIXEL_GIF


def update_data(data, request_data):
    # Ignore request_data
    global taxonomy

    input_choices = ['Professor X', 'Deadpool', 'Aquaman', 'professorX']

    data['keywords'].update({'0001': random.choice(taxonomy['data'][0]['data'])['key']})
    data['keywords'].update({'0002': random.randint(0, 100)})
    data['keywords'].update({'0003': random.choice(input_choices)})
    data['keywords'].update({'0004': bool(random.getrandbits(1))})

    data['human_score'] = random.random()
    return data


def generate_taxonomy():
    global taxonomy
    global taxonomy_name
    global taxonomy_version

    values = [{'label': 'Interests',
               'key': '0001',
               'type': 'dict',
               'data': [{'label': 'Interest: DC', 'key': '0001'},
                        {'label': 'Interest: Marvel', 'key': '0002'},
                        {'label': 'Interest: Marvel: Spiderman', 'key': '0003'},
                        {'label': 'Interest: Marvel: Venom', 'key': '0004'}],
               },
              {'label': 'Comic books owned',
               'key': '0001',
               'type': 'num'},
              {'label': 'Favourite superhero',
               'key': '0001',
               'type': 'input'},
              {'label': 'Registered at comicbookheroes.net',
               'key': '0001',
               'type': 'bool'}
              ]

    taxonomy = {'meta': {'name': taxonomy_name,
                         'version': taxonomy_version},
                'data': values}

    return taxonomy


taxonomy = generate_taxonomy()
