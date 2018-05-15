import urllib

from twisted.web.server import Site
from twisted.web.resource import Resource
from twisted.internet import reactor, defer


from aduser.proxy import const as proxy_const
import db
import json
import utils


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

        utils.attach_tracking_cookie(request)
        return proxy_const.DATA_PROVIDER_CLIENT.pixel(request)


class UserRequest(ChildRequest):

    @defer.inlineCallbacks
    def render_GET(self, request):  # NOSONAR
        if not self.path:
            request.setResponseCode(404)
            defer.returnValue('')

        # Load from db
        consumer = yield db.load_consumer(self.path)

        # Not found? Ask the provider
        if not consumer:
            consumer = proxy_const.DATA_PROVIDER_CLIENT.get_data(urllib.unquote(self.path))
            yield db.save_consumer(consumer)

        defer.returnValue(json.dumps(consumer))


class Info(Resource):
    isLeaf = True

    def render_GET(self, request):  # NOSONAR
        return proxy_const.DATA_PROVIDER_CLIENT.get_schema()


class PixelFactory(ChildFactory):
    child = PixelRequest


class UserFactory(ChildFactory):
    child = UserRequest


def configure_server():

    root = Resource()
    root.putChild("pixel", PixelFactory())
    root.putChild("getData", UserFactory())
    root.putChild("getSchema", Info())

    return reactor.listenTCP(proxy_const.SERVER_PORT, Site(root))
