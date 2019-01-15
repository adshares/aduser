import logging

from mock import MagicMock, patch
from twisted.internet import defer
from twisted.trial.unittest import TestCase

from aduser import const, server_utils
from tests import WebclientTestCase


class InvalidPluginServer(TestCase):

    def test_main(self):

        with self.assertRaises(SystemExit):
            with patch('aduser.plugin.initialize', MagicMock()):
                # Suppress this case of error logging
                logging.disable(logging.ERROR)
                self.port = server_utils.configure_server()

        # Reset logging
        logging.disable(logging.WARNING)


class TestServer(WebclientTestCase):

    @defer.inlineCallbacks
    def test_pixel(self):
        response = yield self.agent.request('GET',
                                            self.url +
                                            '/' +
                                            const.PIXEL_PATH +
                                            '/serverid/userid/nonce.gif')
        self.assertEquals(200, response.code)

        response = yield self.agent.request('GET',
                                            self.url +
                                            '/' +
                                            const.PIXEL_PATH +
                                            '/')
        self.assertEquals(404, response.code)

        response = yield self.agent.request('GET',
                                            self.url +
                                            '/' +
                                            const.PIXEL_PATH +
                                            '/serverid/')
        self.assertEquals(404, response.code)

        response = yield self.agent.request('GET',
                                            self.url +
                                            '/' +
                                            const.PIXEL_PATH +
                                            '/serverid/userid/')
        self.assertEquals(404, response.code)

    @defer.inlineCallbacks
    def test_getPixelPath(self):
        response = yield self.agent.request('GET',
                                            self.url + '/getPixelPath')
        self.assertEquals(200, response.code)

        data = yield self.return_response_json(response)

        response = yield self.agent.request('GET',
                                            bytes(data.replace('{', '').replace('}', '')))
        self.assertEquals(200, response.code)

    @defer.inlineCallbacks
    def test_data(self):

        request_data = {'uid': "000_111",
                        'domain': 'http://example.com',
                        'ua': '',
                        'ip': '212.212.22.1'}

        response = yield self.agent.request('POST',
                                            self.url + '/getData',
                                            None,
                                            self.JsonBytesProducer(request_data))
        self.assertEquals(404, response.code)

        response = yield self.agent.request('GET',
                                            self.url +
                                            '/' +
                                            const.PIXEL_PATH +
                                            '/0/111/nonce.gif')
        self.assertEquals(200, response.code)

        response = yield self.agent.request('POST',
                                            self.url + '/getData',
                                            None,
                                            None)
        self.assertEquals(400, response.code)

        response = yield self.agent.request('POST',
                                            self.url + '/getData',
                                            None,
                                            self.JsonBytesProducer({}))
        self.assertEquals(400, response.code)

        request_data = {'uid': "0_111",
                        'domain': 'http://example.com',
                        'ua': '',
                        'ip': '212.212.22.1'}

        response = yield self.agent.request('POST',
                                            self.url + '/getData',
                                            None,
                                            self.JsonBytesProducer(request_data))
        self.assertEquals(200, response.code)
        data = yield self.return_response_json(response)

        for key in ['keywords', 'human_score', 'uid']:
            self.assertIn(key, data.keys())
            self.assertIsNotNone(data[key])

    @defer.inlineCallbacks
    def test_taxonomy(self):
        response = yield self.agent.request('GET', self.url + '/getTaxonomy')
        self.assertEquals(200, response.code)
        data = yield self.return_response_json(response)

        for key in ['meta', 'data']:
            self.assertIn(key, data.keys())
            self.assertIsNotNone(data[key])

    @defer.inlineCallbacks
    def test_ApiInfo(self):
        response = yield self.agent.request('GET', self.url + '/info')
        self.assertEquals(200, response.code)
