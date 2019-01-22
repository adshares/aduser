import json
import logging

import txmongo
from mock import MagicMock, patch
from twisted.internet import defer, reactor
from twisted.internet.protocol import Protocol
from twisted.trial.unittest import TestCase
from twisted.web.client import Agent
from twisted.web.iweb import IBodyProducer
from zope.interface import implementer

import aduser.data as aduser_data
import aduser.db as aduser_db
from aduser.data import const as data_const
from aduser.iface import server as iface_server
import twisted

twisted.internet.base.DelayedCall.debug = True
logging.disable(logging.WARNING)


class WebclientTestCase(TestCase):
    data_plugin = data_const.DATA_PROVIDER

    class ReceiverProtocol(Protocol):
        def __init__(self, finished):
            self.finished = finished
            self.body = []

        def dataReceived(self, databytes):  # NOSONAR
            self.body.append(databytes)

        def connectionLost(self, reason):  # NOSONAR
            self.finished.callback(''.join(self.body))

    class JsonReceiverProtocol(Protocol):
        def __init__(self, finished):
            self.finished = finished
            self.body = []

        def dataReceived(self, databytes):  # NOSONAR
            self.body.append(databytes)

        def connectionLost(self, reason):  # NOSONAR
            self.finished.callback(json.loads(''.join(self.body)))

    @implementer(IBodyProducer)
    class JsonBytesProducer(object):
        def __init__(self, body):
            self.body = json.dumps(body)
            self.length = len(self.body)

        def startProducing(self, consumer):  # NOSONAR
            consumer.write(self.body)
            return defer.succeed(None)

        def pauseProducing(self):  # NOSONAR
            pass

        def stopProducing(self):  # NOSONAR
            pass

    @defer.inlineCallbacks
    def return_response_body(self, response):
        finished = defer.Deferred()
        response.deliverBody(WebclientTestCase.ReceiverProtocol(finished))
        data = yield finished
        defer.returnValue(data)

    @defer.inlineCallbacks
    def return_response_json(self, response):
        finished = defer.Deferred()
        response.deliverBody(WebclientTestCase.JsonReceiverProtocol(finished))
        data = yield finished
        defer.returnValue(data)

    def setUp(self):  # NOSONAR

        with patch('aduser.data.const.DATA_PROVIDER', self.data_plugin):
            aduser_data.configure_plugin()

        self.port = iface_server.configure_server()

        self.url = 'http://{0}:{1}'.format(self.port.getHost().host,
                                           self.port.getHost().port)

        self.agent = Agent(reactor)
        self.timeout = 1

    def tearDown(self):  # NOSONAR

        self.port.stopListening()
        aduser_data.provider = None


class DBTestCase(TestCase):

    @defer.inlineCallbacks
    def setUp(self):
        self.conn = yield aduser_db.get_mongo_connection()
        self.db = yield aduser_db.get_mongo_db()

        yield aduser_db.configure_db()
        self.timeout = 1

    @defer.inlineCallbacks
    def tearDown(self):
        if aduser_db.MONGO_CONNECTION:
            yield self.conn.drop_database(self.db)
        yield aduser_db.disconnect()


try:
    import mongomock

    class MongoMockTestCase(TestCase):

        def setUp(self):

            aduser_db.MONGO_CONNECTION = None

            self.connection = mongomock.MongoClient()
            self.connection.disconnect = MagicMock()
            self.connection.disconnect.return_value = True

            self.mock_lazyMongoConnectionPool = MagicMock()
            self.mock_lazyMongoConnectionPool.return_value = self.connection
            self.patch(txmongo, 'lazyMongoConnectionPool', self.mock_lazyMongoConnectionPool)

            def mock_create_index(obj, index, *args, **kwargs):
                obj.old_create_index([i[1][0] for i in index.items()], *args, **kwargs)

            mongomock.Collection.old_create_index = mongomock.Collection.create_index
            mongomock.Collection.create_index = mock_create_index

            def mock_find(obj, *args, **kwargs):
                with_cursor = False
                if 'cursor' in kwargs.keys():
                    with_cursor = True
                    del kwargs['cursor']

                cursor = obj.old_find(*args, **kwargs)

                if with_cursor:
                    return [d for d in cursor], ([], None)
                else:
                    return [d for d in cursor]

            mongomock.Collection.old_find = mongomock.Collection.find
            mongomock.Collection.find = mock_find

            def mock_find_one(obj, *args, **kwargs):
                kwargs['limit'] = 1
                cursor = obj.old_find(*args, **kwargs)
                if cursor.count() > 0:
                    return cursor[0]
                return None

            mongomock.Collection.find_one = mock_find_one

        def tearDown(self):
            mongomock.Collection.create_index = mongomock.Collection.old_create_index
            mongomock.Collection.find = mongomock.Collection.old_find


    db_test_case = MongoMockTestCase
except ImportError:
    db_test_case = DBTestCase


class AdUserTestCase:

    def __init__(self):
        self.db_test_case = db_test_case()
        self.web_test_case = WebclientTestCase()

    @defer.inlineCallbacks
    def setUp(self):  # NOSONAR
        yield self.db_test_case.setUp()
        self.web_test_case.setUp()

    @defer.inlineCallbacks
    def tearDown(self):  # NOSONAR
        yield self.db_test_case.tearDown()
        self.web_test_case.tearDown()
