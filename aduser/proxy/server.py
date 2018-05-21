import json
import urllib
import logging

from twisted.web.server import Site
from twisted.web.resource import Resource
from twisted.internet import reactor, defer

from aduser.proxy import const as proxy_const
import db
import utils


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

        tid = utils.attach_tracking_cookie(request)
        self.logger.info("Attaching tracking cookie: {0}".format(tid))
        return proxy_const.DATA_PROVIDER_CLIENT.pixel(request)


class UserRequest(ChildRequest):
    """
    Router handler for endpoints of pixel requests. This is a Twisted Resource.
    """
    @defer.inlineCallbacks
    def render_GET(self, request):  # NOSONAR
        if not self.path:
            request.setResponseCode(404)
            defer.returnValue('')

        # Load from db
        consumer = yield db.load_consumer(self.path)

        # Not found? Ask the provider
        if not consumer:
            yield self.logger.warning("Consumer not found in cache: {0}.".format(self.path))
            consumer = proxy_const.DATA_PROVIDER_CLIENT.get_data(urllib.unquote(self.path))
            if consumer:
                yield db.save_consumer(consumer)

        defer.returnValue(json.dumps(consumer))


class Info(Resource):
    """
    Router handler for endpoints of pixel requests. This is a Twisted Resource.
    """
    isLeaf = True

    def render_GET(self, request):  # NOSONAR
        return proxy_const.DATA_PROVIDER_CLIENT.get_schema()


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
    logger.info("Configured with cookie name: '{0}' with expiration of {1}.".format(proxy_const.COOKIE_NAME,
                                                                              proxy_const.EXPIRY_PERIOD))

    return reactor.listenTCP(proxy_const.SERVER_PORT, Site(root))
