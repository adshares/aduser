from twisted.trial.unittest import TestCase
from aduser.proxy import server
from twisted.web.http import Request
from mock import MagicMock
from twisted.internet import defer


class TestRequest(TestCase):

    proxy_const = MagicMock()
    proxy_const.DATA_PROVIDER_CLIENT = MagicMock()
    proxy_const.pixel = MagicMock()

    @defer.inlineCallbacks
    def test_pixel(self):
        req = Request(channel=MagicMock())
        req.setResponseCode = MagicMock()
        req.addCookie = MagicMock()

        resource = server.PixelRequest()
        response = yield resource.render_GET(req)
        self.assertIsNotNone(response)
        self.assertEqual(response, '')
        req.setResponseCode.assert_called_with(404)

        resource = server.PixelRequest('request_id')
        response = yield resource.render_GET(req)
        self.assertIsNotNone(response)
        req.setResponseCode.assert_called_with(302)
        req.addCookie.assert_called()

    @defer.inlineCallbacks
    def test_get_data(self):
        req = Request(channel=MagicMock())
        req.setResponseCode = MagicMock()
        req.addCookie = MagicMock()

        resource = server.UserRequest()
        response = yield resource.render_GET(req)
        self.assertIsNotNone(response)
        self.assertEqual(response, '')
        req.setResponseCode.assert_called_with(404)

    @defer.inlineCallbacks
    def test_get_schema(self):
        req = Request(channel=MagicMock())
        resource = server.Info()

        exp = resource.render_GET(req)
        self.assertEqual(NotImplemented, exp)
        yield ''
