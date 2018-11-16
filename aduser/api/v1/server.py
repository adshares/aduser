import json

from twisted.internet import defer
from twisted.web.resource import Resource
from twisted.web.server import NOT_DONE_YET

from aduser import const, plugin, utils

path_template = json.dumps({"path": const.PIXEL_PATH + '?{adserver_id}_{user_id}.gif'})


class PixelRequest(Resource):
    """
    Routing class for pixels.
    """
    def render_GET(self, request):  # NOSONAR
        utils.attach_tracking_cookie(request)
        return plugin.data.pixel(request)


class PixelPathRequest(Resource):
    """
    Routing class for pixel paths.
    """
    def render_GET(self, request):  # NOSONAR
        request.setHeader(b"content-type", b"text/javascript")
        return path_template


class PixelRequest(Resource):
    """
    Router handler for endpoints of pixel requests. This is a `twisted.web.resource.Resource`.
    """
    def render_GET(self, request):  # NOSONAR

        utils.attach_tracking_cookie(request)
        return plugin.data.pixel(request)


class DataRequest(Resource):
    """
    Router handler for endpoints of data requests. This is a `twisted.web.resource.Resource`.
    """
    def render_GET(self, request):  # NOSONAR
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
            request.setHeader(b"content-type", b"text/javascript")

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


class ApiInfoRequest(Resource):
    """
    Router handler for normalization of targeting data. This is a `twisted.web.resource.Resource`.
    """
    isLeaf = True

    @staticmethod
    def render_GET(request):
        return json.dumps({})
