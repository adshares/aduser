import json
import logging

from twisted.internet import reactor, defer
from twisted.web.server import Site
from twisted.web.resource import Resource

from aduser.simple_provider.client import const as const
import db


class ChildRequest(Resource):
    isLeaf = True

    def __init__(self, path=None):
        Resource.__init__(self)
        self.path = path
        self.logger = logging.getLogger(__name__)


class ChildFactory(Resource):

    def render_GET(self, request):  # NOSONAR
        request.setResponseCode(404)
        return ''

    def getChild(self, path, request):  # NOSONAR
        return self.child(path)


class PixelRequest(ChildRequest):

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


class PixelFactory(ChildFactory):
    child = PixelRequest


class UserFactory(ChildFactory):
    child = UserRequest


class Info(Resource):
    isLeaf = True

    def render_GET(self, request):  # NOSONAR
        return ''


def configure_server():

    root = Resource()
    root.putChild("pixel", PixelFactory())
    root.putChild("getData", UserFactory())
    root.putChild("getSchema", Info())

    logger = logging.getLogger(__name__)
    logger.info("Initializing server.")

    return reactor.listenTCP(const.SERVER_PORT, Site(root))
