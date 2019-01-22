from twisted.internet import reactor
from twisted.web.resource import Resource
from twisted.web.server import Site

from aduser.iface import const as iface_const, resources as iface_resources


def configure_entrypoint():
    """
    Configure the entry points for resources.

    :return: An instance of root Resource.
    """
    # Set up endpoints.
    root = Resource()
    root.putChild("getPixelPath", iface_resources.PixelPathResource())
    root.putChild("getData", iface_resources.DataResource())
    root.putChild("getTaxonomy", iface_resources.TaxonomyResource())
    root.putChild("info", iface_resources.ApiInfoResource())
    root.putChild(iface_const.PIXEL_PATH, iface_resources.PixelFactory())

    return root


def configure_server():
    """
    Initialize the server.

    :return: An instance of a class implementing `twisted.internet.interfaces.IListeningPort`.
    """
    # Set up endpoints.
    root = configure_entrypoint()
    return reactor.listenTCP(iface_const.SERVER_PORT, Site(root))
