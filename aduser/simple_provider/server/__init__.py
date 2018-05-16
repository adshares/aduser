from twisted.internet import reactor, defer
from aduser.simple_provider.client import const as const

from twisted.web.server import Site
from twisted.web.resource import Resource
import db
import json


class ChildRequest(Resource):
    isLeaf = True

    def __init__(self, path=None):
        Resource.__init__(self)
        self.path = path


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
            defer.returnValue(json.dumps(consumer))

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

    return reactor.listenTCP(const.SERVER_PORT, Site(root))
