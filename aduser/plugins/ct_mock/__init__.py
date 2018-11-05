import logging
import urllib
import json

from twisted.internet import defer, reactor
from twisted.web.util import redirectTo
from twisted.web.client import Agent

from utils import StringProducer, ReceiverProtocol

plugin_name = 'CT_mock'
logger = logging.getLogger(__name__)

redirect_url = 'ctmock.example'
user_data_url = 'ctmock.example'


def init():
    logger.info("Initializing default pixel plugin.")


def pixel(request):
    """

    :return: pixel image
    """
    tid = request.cookies[-1]
    ct_url = "{0}/{1}".format(redirect_url, urllib.quote(tid))
    logger.info("Redirecting to {0}".format(ct_url))
    return redirectTo(ct_url, request)


@defer.inlineCallbacks
def get_ct_user_data(tid):
    """
    Get user profile from provider server.

    :param user_identifier: User identifier
    :return: User profile dictionary.
    """
    agent = Agent(reactor)
    data_url = '{0}/get_data/{1}'.format(user_data_url, tid)

    self.logger.info("Fetching data from {0}".format(data_url))
    response = yield agent.request('GET', data_url)

    finished = defer.Deferred()
    response.deliverBody(ReceiverProtocol(finished))
    data = yield finished
    defer.returnValue(json.loads(data) if data else None)


def update_user(user_object):
    """

    :param user_identifier:
    :return: User profile (with keywords)
    """
    return NotImplemented


def schema():
    schema_data = {'name': 'ct_mock',
                   'version': 0.1,
                   'children': [
                       {'label': 'Interest: Family & Parenting: Family & Parenting,',
                        'key': '200039'}
                        ]
                   }

    return schema_data


@defer.inlineCallbacks
def get_consumer_mapping(uid):
    consumer = yield db.load_mapping(tid)
    defer.returnValue(consumer)


@defer.inlineCallbacks
def get_consumer_from_db(uid):
    consumer = yield db.load_consumer(tid)
    defer.returnValue(consumer)