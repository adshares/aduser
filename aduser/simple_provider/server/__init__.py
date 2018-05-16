import json
import logging

from twisted.internet import reactor, defer
from twisted.web.server import Site
from twisted.web.resource import Resource

from aduser.simple_provider.client import const as const
import db


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


@defer.inlineCallbacks
class UserRequest(ChildRequest):
    """
    Router handler for endpoints of pixel requests. This is a Twisted Resource.
    """
    def render_GET(self, request):  # NOSONAR
        if not self.path:
            request.setResponseCode(404)
            defer.returnValue('')

        tid = request.getCookie(const.REQUEST_COOKIE_NAME)

        # Load from db
        consumer = yield db.load_consumer(tid)

        if consumer:
            yield self.logger.info("Consumer found.")
            yield self.logger.debug(consumer)
            defer.returnValue(json.dumps(consumer))

            yield self.logger.warning("No consumer found.")
        defer.returnValue(json.dumps([]))


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

    return reactor.listenTCP(const.SERVER_PORT, Site(root))
