from test_server_utils import TestServer


class ExampleTestServer(TestServer):
    data_plugin = 'aduser.plugins.examples.example'


class SkeletonTestServer(TestServer):
    data_plugin = 'aduser.plugins.skeleton'


class IPapiTestServer(TestServer):
    data_plugin = 'aduser.plugins.examples.ipapi'


class BrowscapTestServer(TestServer):
    data_plugin = 'aduser.plugins.examples.browscap'


class SimpleTestServer(TestServer):
    data_plugin = 'aduser.plugins.simple'


class DemoTestServer(SimpleTestServer):
    data_plugin = 'aduser.plugins.demo'

