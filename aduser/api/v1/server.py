import json
import logging

from twisted.internet import defer
from twisted.web.resource import NoResource, Resource
from twisted.web.server import NOT_DONE_YET

from aduser import const, plugin, utils
from aduser.db import utils as db_utils


class PixelPathResource(Resource):
    """
    Routing class for pixel paths.
    """
    isLeaf = True

    @staticmethod
    def render_GET(request):  # NOSONAR
        request.setHeader(b"content-type", b"application/json")
        return '"http://{0}:{1}/{2}'.format(request.getHost().host,
                                            request.getHost().port,
                                            const.PIXEL_PATH) + '/{adserver_id}/{user_id}/{nonce}.gif"'


class PixelFactory(Resource):
    """
    Router handler for endpoints of pixel requests. This is a `twisted.web.resource.Resource`.
    """

    def getChild(self, adserver_id, request):
        if adserver_id == '':
            return NoResource()
        return AdServerPixelFactory(adserver_id)


class AdServerPixelFactory(Resource):

    def __init__(self, adserver_id):
        Resource.__init__(self)
        self.adserver_id = adserver_id

    def getChild(self, user_id, request):
        if user_id == '':
            return NoResource()
        return UserPixelFactory(self.adserver_id, user_id)


class UserPixelFactory(Resource):
    def __init__(self, adserver_id, user_id):
        Resource.__init__(self)
        self.adserver_id = adserver_id
        self.user_id = user_id

    def getChild(self, nonce, request):
        if nonce == '':
            return NoResource()
        return UserPixelResource(self.adserver_id, self.user_id, nonce)


class UserPixelResource(Resource):

    isLeaf = True

    def __init__(self, adserver_id, user_id, nonce):
        Resource.__init__(self)
        self.adserver_id = adserver_id
        self.user_id = user_id
        self.nonce = nonce

    def render_GET(self, request):  # NOSONAR

        tid = utils.attach_tracking_cookie(request)
        db_utils.update_mapping({'tracking_id': tid,
                                 'server_user_id': self.adserver_id + '_' + self.user_id})
        db_utils.update_pixel({'tracking_id': tid,
                               'request': [h for h in request.requestHeaders.getAllRawHeaders()]})
        logging.info({'tracking_id': tid,
                      'request': [h for h in request.requestHeaders.getAllRawHeaders()]})
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
        logger = logging.getLogger(__name__)
        try:
            post_data = json.loads(request.content.read())
        except ValueError:
            logger.debug('ValueError')
            logger.debug(request.content.read())
            request.setResponseCode(400)
            request.finish()
            return

        # Validate request data
        try:
            request_data = {'site': {},
                            'device': {}}

            request_data['device']['ip'] = post_data['ip']
            request_data['device']['ua'] = post_data['ua']

            default_data = {'uid': post_data['uid'],
                            'human_score': 0.5,
                            'keywords': {}}

        except KeyError:
            logger.debug('KeyError')

            request.setResponseCode(400)
            request.finish()
            return

        try:
            user_map = yield db_utils.get_mapping(post_data['uid'])
            cached_data = yield db_utils.get_user_data(user_map['tracking_id'])
        except TypeError:
            logger.debug('User not found')

            request.setResponseCode(404)
            request.finish()
            return

        logger.debug(cached_data)
        if cached_data:
            default_data['keywords'] = cached_data['keywords']

        data = yield plugin.data.update_data(default_data, request_data)

        data.update({'tracking_id': user_map['tracking_id']})
        yield db_utils.update_user_data(data)

        del data['tracking_id']

        logger.debug(data)

        yield request.write(json.dumps(data))
        request.setHeader(b"content-type", b"application/json")

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
