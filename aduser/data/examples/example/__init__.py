import random
from base64 import b64decode
from aduser.data import const as data_const

#: Base64 encoded 1x1 GIF
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")

#: Meta information - taxonomy name
taxonomy_name = 'example'

#: Meta information - taxonomy version
taxonomy_version = '0.0.1'

#: Taxonomy meta information with data
taxonomy = {'meta': {'name': taxonomy_name,
                     'version': taxonomy_version},
            'data': [{'label': 'Interests',
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
                     ]}


def score(tracking_id, request):
    """
    :return: score JS
    """
    return None


def score_data(tracking_id, token, request):
    """
    :return: human score
    """
    return data_const.DEFAULT_HUMAN_SCORE


def pixel(tracking_id, request):
    """
    :return: pixel image
    """
    request.setHeader(b"content-type", b"image/gif")
    return PIXEL_GIF


def update_data(data, request_data):
    """
    Choose random values from a small list.

    :param data: User data to update
    :param request_data: Request data (ignored)
    :return: Updated user data
    """
    global taxonomy

    input_choices = ['Professor X', 'Deadpool', 'Aquaman', 'professorX']

    data['keywords'].update({'0001': random.choice(taxonomy['data'][0]['data'])['key']})
    data['keywords'].update({'0002': random.randint(0, 100)})
    data['keywords'].update({'0003': random.choice(input_choices)})
    data['keywords'].update({'0004': bool(random.getrandbits(1))})

    data['human_score'] = random.random()
    return data
