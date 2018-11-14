import json
import logging

from twisted.internet import defer
from twisted.web.resource import Resource
from twisted.web.server import NOT_DONE_YET

from aduser import plugin, utils


class ChildRequest(Resource):
    """
    Common base class for pixel and user data requests.
    """
    isLeaf = True

    def __init__(self, path=None):
        Resource.__init__(self)
        self.path = path
        self.logger = logging.getLogger(__name__)


class PixelRequest(ChildRequest):
    """
    Router handler for endpoints of pixel requests. This is a `twisted.web.resource.Resource`.
    """
    def render_GET(self, request):  # NOSONAR
        if not self.path:
            request.setResponseCode(404)
            return ''

        utils.attach_tracking_cookie(request)
        return plugin.data.pixel(request)


class DataRequest(ChildRequest):
    """
    Router handler for endpoints of data requests. This is a `twisted.web.resource.Resource`.
    """
    def render_POST(self, request):  # NOSONAR
        self.handle_data(request)

        return NOT_DONE_YET

    @defer.inlineCallbacks
    def handle_data(self, request):

        request_data = json.loads(request.content.read())

        # Validate request data
        try:
            default_data = {'uid': request_data['user']['uid'],
                            'human_score': 1.0,
                            'keywords': {}}

            data = yield plugin.data.update_data(default_data, request_data)

            yield request.write(json.dumps(data))

        except KeyError:
            request.setResponseCode(400)

        yield request.finish()


class SchemaRequest(Resource):
    """
    Router handler for endpoints of schema requests. This is a `twisted.web.resource.Resource`.
    """
    isLeaf = True

    @staticmethod
    def render_GET(request):  # NOSONAR

        request.setHeader(b"content-type", b"text/javascript")
        return json.dumps(plugin.data.schema)


class NormalizationRequest(Resource):
    """
    Router handler for normalization of targeting data. This is a `twisted.web.resource.Resource`.
    """
    isLeaf = True

    @staticmethod
    def render_POST(request):
        data_to_normalize = json.loads(request.content.read())

        normalized_data = {'ver': plugin.data.schema['meta']['ver'],
                           'name': plugin.data.schema['meta']['name'],
                           'data': plugin.data.normalize(data_to_normalize)}

        request.setHeader(b"content-type", b"text/javascript")
        return json.dumps(normalized_data)
