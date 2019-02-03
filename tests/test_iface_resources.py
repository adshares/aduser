import json

from mock import MagicMock, patch
from twisted.internet import defer
from twisted.trial.unittest import TestCase
from twisted.web.resource import NoResource, Resource

from aduser.iface import resources
from aduser.data import configure_plugin


class TestResources(TestCase):

    def setUp(self):
        configure_plugin()

    def test_pixel_path_resource(self):

        request = MagicMock()

        json_pixel_path = resources.PixelPathResource.render_GET(request)

        try:
            pixel_path = json.loads(json_pixel_path)

            # Verify it returns a string starting with http
            self.assertTrue(pixel_path.startswith('http'))

            # Verify it returns application/json
            request.setHeader.assert_called_with(b"content-type", b"application/json")

        except ValueError:
            # Verify it returns valid JSON
            self.fail('ValueError. It probably means invalid JSON')

    def test_pixel_resource(self):

        request = MagicMock()
        request.getCookie.return_value = None

        upr = resources.UserPixelResource(adserver_id='server_id',
                                          user_id='user_id',
                                          nonce='nonce')

        upr.render_GET(request)

        # Verify it returns application/json
        request.setHeader.assert_called_with(b"content-type", b"image/gif")

    def test_taxonomy_resource(self):

        request = MagicMock()

        json_taxonomy = resources.TaxonomyResource.render_GET(request)

        try:
            taxonomy = json.loads(json_taxonomy)

            for k in ['meta', 'data']:
                self.assertIn(k, taxonomy.keys())

            # Verify it returns application/json
            request.setHeader.assert_called_with(b"content-type", b"application/json")

        except ValueError:
            # Verify it returns valid JSON
            self.fail('ValueError. It probably means invalid JSON')

    def test_api_resource(self):

        request = MagicMock()

        json_api = resources.ApiInfoResource.render_GET(request)

        try:
            json.loads(json_api)

            # Verify it returns application/json
            request.setHeader.assert_called_with(b"content-type", b"application/json")

        except ValueError:
            # Verify it returns valid JSON
            self.fail('ValueError. It probably means invalid JSON')

    def test_data_resource_post(self):

        # Verify it returns application/json
        request = MagicMock()
        request.content.read.return_value = '{}'

        dr = resources.DataResource()
        dr.render_POST(request)
        request.setHeader.assert_called_with(b"content-type", b"application/json")

    @defer.inlineCallbacks
    def test_data_resource_handle_data(self):

        dr = resources.DataResource()

        # ** Empty request **
        request = MagicMock()

        request.content.read.return_value = '{}'
        yield dr.handle_data(request)

        request.write.assert_not_called()
        request.finish.assert_called()

        # Verify proper error code
        request.setResponseCode.assert_called_with(400)

        # ** Invalid JSON request **
        request = MagicMock()

        request.content.read.return_value = '}{'
        yield dr.handle_data(request)

        request.write.assert_not_called()
        request.finish.assert_called()

        # Verify proper error code
        request.setResponseCode.assert_called_with(400)

        # ** Valid request, but user not found **
        request = MagicMock()

        request.content.read.return_value = '{"ip": "1", "ua": "agent", "uid": "uid"}'
        yield dr.handle_data(request)

        request.write.assert_not_called()
        request.finish.assert_called()

        # Verify proper error code
        request.setResponseCode.assert_called_with(404)

        # ** Valid request with cached data **
        request = MagicMock()

        request.content.read.return_value = '{"ip": "1", "ua": "agent", "uid": "uid"}'

        db_utils = MagicMock()
        db_utils.get_mapping.return_value = {'tracking_id': 'tid'}
        db_utils.get_user_data.return_value = {'tracking_id': 'tid',
                                               'keywords': {},
                                               'human_score': 1.0}

        with patch('aduser.iface.resources.db_utils', db_utils):
            yield dr.handle_data(request)

        request.write.assert_called()
        request.finish.assert_called()

        # Test with resources cache

        request = MagicMock()
        request.content.read.return_value = '{"ip": "1", "ua": "agent", "uid": "uid"}'

        yield dr.handle_data(request)

        request.write.assert_called()
        request.finish.assert_called()

    @defer.inlineCallbacks
    def test_data_resource_handle_data_without_cached_data(self):

        dr = resources.DataResource()

        request = MagicMock()
        request.content.read.return_value = '{"ip": "1", "ua": "agent", "uid": "uid"}'

        db_utils = MagicMock()
        db_utils.get_mapping.return_value = {'tracking_id': 'tid'}
        db_utils.get_user_data.return_value = None

        with patch('aduser.iface.resources.iface_const.DEBUG_WITHOUT_CACHE', 1):
            with patch('aduser.iface.resources.db_utils', db_utils):
                yield dr.handle_data(request)

        request.write.assert_called()
        request.finish.assert_called()


class TestFactories(TestCase):

    def test_pixel_factory(self):
        request = MagicMock()

        factory = resources.PixelFactory()
        child = factory.getChild(adserver_id='server_id', request=request)
        self.assertIsNotNone(child)
        self.assertTrue(isinstance(child, Resource))

        child = factory.getChild(adserver_id='', request=request)
        self.assertIsNotNone(child)
        self.assertTrue(isinstance(child, NoResource))

    def test_adserver_pixel_factory(self):
        request = MagicMock()

        factory = resources.AdServerPixelFactory('server_id')
        child = factory.getChild(user_id='user_id', request=request)
        self.assertIsNotNone(child)
        self.assertTrue(isinstance(child, Resource))

        child = factory.getChild(user_id='', request=request)
        self.assertIsNotNone(child)
        self.assertTrue(isinstance(child, NoResource))

    def test_user_pixel_factory(self):
        request = MagicMock()

        factory = resources.UserPixelFactory('server_id', 'user_id')
        child = factory.getChild(nonce='nonce', request=request)
        self.assertIsNotNone(child)
        self.assertTrue(isinstance(child, Resource))

        child = factory.getChild(nonce='', request=request)
        self.assertIsNotNone(child)
        self.assertTrue(isinstance(child, NoResource))
