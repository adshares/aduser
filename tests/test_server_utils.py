import os

from twisted.internet import defer

from tests import WebclientTestCase


class TestServer(WebclientTestCase):

    @defer.inlineCallbacks
    def test_pixel(self):
        response = yield self.agent.request('GET', self.url + '/' + os.getenv('ADUSER_PIXEL_PATH'))
        self.assertEquals(200, response.code)

    @defer.inlineCallbacks
    def test_getPixelPath(self):
        response = yield self.agent.request('GET', self.url + '/getPixelPath')
        self.assertEquals(200, response.code)

        data = yield self.return_response_json(response)
        response = yield self.agent.request('GET', self.url + '/' + bytes(data))
        self.assertEquals(200, response.code)

    @defer.inlineCallbacks
    def test_data(self):
        response = yield self.agent.request('GET',
                                            self.url + '/getData',
                                            None,
                                            self.JsonBytesProducer({}))
        self.assertEquals(400, response.code)

        request_data = {'user': {'uid': 111},
                        'site': {'domain': 'http://example.com',
                                 'keywords': ['sports', 'football']},
                        'device': {'ua': '',
                                   'ip': '212.212.22.1'}}

        response = yield self.agent.request('GET',
                                            self.url + '/getData?'
                                            + 'uid=111&'
                                            + 'domain=example.com&'
                                            + 'ip=212.212.22.1&'
                                            + 'ua=fake_ua',
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
