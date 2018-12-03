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

    @staticmethod
    def render_GET(request):  # NOSONAR
        request.setHeader(b"content-type", b"application/json")
        return '"{0}'.format(const.PIXEL_PATH) + '?{adserver_id}_{user_id}.gif"'


class PixelResource(Resource):
    """
    Router handler for endpoints of pixel requests. This is a `twisted.web.resource.Resource`.
    """
    isLeaf = True

    @staticmethod
    def render_GET(request):  # NOSONAR

        utils.attach_tracking_cookie(request)
        return plugin.data.pixel(request)


class DataResource(Resource):
    """
    Router handler for endpoints of data requests. This is a `twisted.web.resource.Resource`.
    """
    isLeaf = True

    def render_POST(self, request):  # NOSONAR
        self.handle_data(request)

        return NOT_DONE_YET

    @defer.inlineCallbacks
    def handle_data(self, request):

        # Validate request data
        try:
            request_data = {'site': {},
                            'device': {}}

            post_data = json.loads(request.content.read())

            request_data['device']['ip'] = post_data['ip']
            request_data['device']['ua'] = post_data['ua']

            default_data = {'uid': post_data['uid'],
                            'human_score': 1.0,
                            'keywords': []}

            data = yield plugin.data.update_data(default_data, request_data)

            yield request.write(json.dumps(data))
            request.setHeader(b"content-type", b"application/json")

        except KeyError:
            request.setResponseCode(400)

        yield request.finish()


class TaxonomyResource(Resource):
    """
    Router handler for endpoints of schema requests. This is a `twisted.web.resource.Resource`.
    """
    isLeaf = True

    @staticmethod
    def render_GET(request):  # NOSONAR

        request.setHeader(b"content-type", b"application/json")
        return json.dumps(plugin.data.taxonomy)


class ApiInfoResource(Resource):
    """
    Router handler for normalization of targeting data. This is a `twisted.web.resource.Resource`.
    """
    isLeaf = True

    @staticmethod
    def render_GET(request):
        request.setHeader(b"content-type", b"application/json")
        return json.dumps({})
