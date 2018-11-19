import json

from twisted.internet import defer
from twisted.web.resource import Resource
from twisted.web.server import NOT_DONE_YET

from aduser import const, plugin, utils


class PixelPathResource(Resource):
    """
    Routing class for pixel paths.
    """
    isLeaf = True

    def render_GET(self, request):  # NOSONAR
        request.setHeader(b"content-type", b"text/javascript")
        return '"{0}'.format(const.PIXEL_PATH) + '?{adserver_id_{user_id}.gif"'


class PixelResource(Resource):
    """
    Router handler for endpoints of pixel requests. This is a `twisted.web.resource.Resource`.
    """
    isLeaf = True

    def render_GET(self, request):  # NOSONAR

        utils.attach_tracking_cookie(request)
        return plugin.data.pixel(request)


class DataResource(Resource):
    """
    Router handler for endpoints of data requests. This is a `twisted.web.resource.Resource`.
    """
    isLeaf = True

    def render_GET(self, request):  # NOSONAR
        self.handle_data(request)

        return NOT_DONE_YET

    @defer.inlineCallbacks
    def handle_data(self, request):

        # Validate request data
        try:
            request_data = {'site': {},
                            'device': {}}

            request_data['device']['ip'] = request.args['ip'][0]
            request_data['device']['ua'] = request.args['ua'][0]

            default_data = {'uid': request.args['uid'][0],
                            'human_score': 1.0,
                            'keywords': {}}

            data = yield plugin.data.update_data(default_data, request_data)

            yield request.write(json.dumps(data))
            request.setHeader(b"content-type", b"text/javascript")

        except KeyError:
            request.setResponseCode(400)

        yield request.finish()


class TaxonomyResource(Resource):
    """
    Router handler for endpoints of schema requests. This is a `twisted.web.resource.Resource`.
    """
    isLeaf = True

    def render_GET(self, request):  # NOSONAR

        request.setHeader(b"content-type", b"text/javascript")
        return json.dumps(plugin.data.taxonomy)


class ApiInfoResource(Resource):
    """
    Router handler for normalization of targeting data. This is a `twisted.web.resource.Resource`.
    """
    isLeaf = True

    def render_GET(self, request):
        return json.dumps({})
