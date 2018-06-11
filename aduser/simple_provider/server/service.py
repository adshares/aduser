import json
import logging

from twisted.internet import reactor, defer
from twisted.web.server import Site, NOT_DONE_YET
from twisted.web.resource import Resource

from aduser.simple_provider.server import const
import db

DATA_SOURCES = []


class ChildRequest(Resource):
    """
    Common base class for pixel and userdata requests.
    """
    isLeaf = True

    def __init__(self, path=None):
        Resource.__init__(self)
        self.path = path
        self.logger = logging.getLogger(__name__)


class ChildFactory(Resource):
    """
    Common base class for routing pixel and user data requests.
    """
    def render_GET(self, request):  # NOSONAR
        request.setResponseCode(404)
        return ''

    def getChild(self, path, request):  # NOSONAR
        return self.child(path)


class PixelRequest(ChildRequest):
    """
    Router handler for endpoints of pixel requests. This is a Twisted Resource.
    """
    def render_GET(self, request):  # NOSONAR
        if not self.path:
            request.setResponseCode(404)
            return ''

        # TODO: Add user to database if doesn't exist
        self.logger.info("Returning pixel image.")
        request.setHeader(b"content-type", b"image/gif")
        return const.PIXEL_GIF


class UserRequest(ChildRequest):
    """
    Router handler for endpoints of pixel requests. This is a Twisted Resource.
    """
    def render_GET(self, request):  # NOSONAR
        if not self.path:
            request.setResponseCode(404)
            return ''

        self.handle_data(request, self.path)

        return NOT_DONE_YET

    @defer.inlineCallbacks
    def handle_data(self, request, tid):

        data = {'user': {'consumer_id': tid,
                         'request_id': None,
                         'client_ip': None,
                         'cookies': [],
                         'headers': {},
                         'human_score': 1.0,
                         'keywords': {}},
                'site': {}}

        consumer = {}

        # Load from db
        if tid:
            consumer = yield db.load_consumer(tid)

        if consumer:
            yield self.logger.info("Consumer found.")
            yield self.logger.debug(consumer)

            del consumer['_id']

            data['user'].update(consumer)

            yield self.logger.info(json.dumps(data))

        else:
            yield self.logger.warning("No consumer found.")
            for source in const.USER_DATA_SOURCES:
                source.update_user(data['user'])

            yield db.save_consumer(data['user'])

            yield self.logger.info(json.dumps(data))

        yield request.write(json.dumps(data))
        yield request.finish()


class Info(Resource):
    """
    Router handler for endpoints of pixel requests. This is a Twisted Resource.
    """
    isLeaf = True

    def render_GET(self, request):  # NOSONAR
        return ''


class PixelFactory(ChildFactory):
    """
    Routing class for pixels.
    """
    child = PixelRequest


class UserFactory(ChildFactory):
    """
    Routing class for user data.
    """
    child = UserRequest


def initialize_sources():
    """
    Initialize data sources.

    :return:
    """
    global DATA_SOURCES

    for source in const.USER_DATA_SOURCES:
        source.init()
        DATA_SOURCES.append(source)


def configure_server():
    """
    Initialize the server.

    :return:
    """
    root = Resource()
    root.putChild("pixel", PixelFactory())
    root.putChild("getData", UserFactory())
    root.putChild("getSchema", Info())

    logger = logging.getLogger(__name__)
    logger.info("Initializing server.")
    logger.info("Configured with cookie name: '{0}' with expiration of {1}.".format(const.COOKIE_NAME,
                                                                                    const.EXPIRY_PERIOD))
    db.configure_db()
    initialize_sources()

    return reactor.listenTCP(const.SERVER_PORT, Site(root))
