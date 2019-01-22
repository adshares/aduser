from mock import patch
from twisted.internet import defer

from aduser.iface import const as iface_const
from tests import AdUserTestCase


class TestServer(AdUserTestCase):

    @defer.inlineCallbacks
    def test_pixel(self):

        base_pixel_url = self.url + '/' + iface_const.PIXEL_PATH

        response = yield self.agent.request('GET',
                                            base_pixel_url + '/serverid/userid/nonce.gif')
        self.assertEquals(200, response.code)

        response = yield self.agent.request('GET',
                                            base_pixel_url + '/')
        self.assertEquals(404, response.code)

        response = yield self.agent.request('GET',
                                            base_pixel_url + '/serverid/')
        self.assertEquals(404, response.code)

        response = yield self.agent.request('GET',
                                            base_pixel_url + '/serverid/userid/')
        self.assertEquals(404, response.code)

    @defer.inlineCallbacks
    def test_getPixelPath(self):

        response = yield self.agent.request('GET', self.url + '/getPixelPath')
        self.assertEquals(200, response.code)

        data = yield self.return_response_json(response)

        response = yield self.agent.request('GET',
                                            bytes(data.replace('{', '').replace('}', '')))
        self.assertEquals(200, response.code)

    @defer.inlineCallbacks
    def test_data(self):

        with patch('aduser.iface.const.DEBUG_WITHOUT_CACHE', 1):
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
                                                iface_const.PIXEL_PATH +
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
            print data
            for key in ['keywords', 'human_score', 'uid']:
                self.assertIn(key, data.keys())
                self.assertIsNotNone(data[key])
            print data

    @defer.inlineCallbacks
    def test_data_with_cache(self):

        with patch('aduser.iface.const.DEBUG_WITHOUT_CACHE', 0):
            # Cache test

            request_data = {'uid': "0_111",
                            'domain': 'http://example.com',
                            'ua': '',
                            'ip': '212.212.22.1'}

            response = yield self.agent.request('POST',
                                                self.url + '/getData',
                                                None,
                                                self.JsonBytesProducer(request_data))
            self.assertEquals(200, response.code)
            print response
            data = yield self.return_response_json(response)

            response = yield self.agent.request('POST',
                                                self.url + '/getData',
                                                None,
                                                self.JsonBytesProducer(request_data))
            self.assertEquals(200, response.code)
            data_cached = yield self.return_response_json(response)

            self.assertEqual(data, data_cached)
            print data

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
