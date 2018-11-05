from twisted.internet import defer
from tests import WebclientTestCase


class TestInitialize(WebclientTestCase):

    @defer.inlineCallbacks
    def test_pixel(self):
        response = yield self.agent.request('GET', self.url + '/pixel/')
        self.assertEquals(404, response.code)

        response = yield self.agent.request('GET', self.url + '/pixel/user_identification')
        self.assertEquals(200, response.code)

    @defer.inlineCallbacks
    def test_data(self):
        response = yield self.agent.request('POST',
                                            self.url + '/getData',
                                            None,
                                            self.JsonBytesProducer({}))
        self.assertEquals(400, response.code)

        request_data = {'user': {'uid': 111},
                        'site': {'domain': 'http://example.com',
                                 'keywords': ['sports', 'football']},
                        'device': {'ua': '',
                                   'ip': '212.212.22.1'}}

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
    def test_schema(self):
        response = yield self.agent.request('GET', self.url + '/getSchema')
        self.assertEquals(200, response.code)
        data = yield self.return_response_json(response)

        for key in ['meta', 'values']:
            self.assertIn(key, data.keys())
            self.assertIsNotNone(data[key])

    @defer.inlineCallbacks
    def test_normalize(self):
        response = yield self.agent.request('POST',
                                            self.url + '/normalize',
                                            None,
                                            self.JsonBytesProducer({}))
        self.assertEquals(200, response.code)
        data = yield self.return_response_json(response)

        for key in ['ver', 'data', 'name']:
            self.assertIn(key, data.keys())
            self.assertIsNotNone(data[key])
