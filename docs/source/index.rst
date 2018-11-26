Welcome to AdUser's documentation!
==================================

What is AdUser?
---------------

AdUser is an element in Adshares network. It provides information about consumers and sites to the ad network, through AdServer. Three most important consumer information are:

    * human score - likelihood of this user being a real human and not a bot
    * consumer keywords - set of keywords describing the consumer, eg. age, country and interests.
    * site keywords - set of keywords describing the site, eg. cars, sports.

Deployment
==========

Installation
------------

Full installation instructions can be found in `README.md <https://github.com/adshares/aduser/blob/master/README.md>`_. AdUser is run within a Virtualenv. Dependencies are provided in requirements.txt and you can use pip to install them.

Make sure you set up the ``$ADUSER_ROOT`` environment variable to point to the root directory of AdUser - the directory containing the aduser package.


User Guide
==========

Goal of AdUser
--------------

Inform AdServer about consumers. This information is used in ad targeting.

Architecture
------------
AdUser is a Twisted app, backed by MongoDB and communicating via HTTP GET requests.

Python stack is as follows:

    * Twisted for the core network communication and asynchronous event handling
    * txmongo for asynchronous MongoDB communication

Some plugins may require additional packages, eg.:

    * python-geoip for MaxMind GeoIP integration
    * pybrowscap for Browscap integration
    * ip2location for IP2Location integration

See plugins documentation.

Default server
--------------
In the default implementation, the proxy and server are two different web services with identical interfaces. The proxy receives the request from AdServer, attaches the tracking cookie (which acts as user identifier), checks the cache and asks the server for more information. The server creates a user profile by attaching information coming from `Browscap <https://browscap.org/>`_ and geolocation (`MaxMind GeoIP <https://www.maxmind.com/en/geoip-demo>`_).

`ip-api <http://ip-api.com>`_ module is provided as an example integration, but switched off by default, because it requires a license for commercial use.

Consumer profile
----------------
Default consumer profile has no keywords and a human score of 1.0 (fully human).

Browsecap provides information about browser capabilities, eg. javascript.
Browsecap is also used to change the human score to 0, if the browser is identified as a crawler.
Geolocation adds geographical information, eg. city or country.

Development
===========

Extending functionality
-----------------------

The most interesting part to extend is configuration of UserDataSources. When AdUser builds user profile, it passes the user profile to an ordered list of data sources. The list is defined in in server configuration (:py:const:`aduser.simple_provider.server.const.USER_DATA_SOURCES`). You can create your own sources by extending the :py:class:`aduser.simple_provider.server.data_sources.UserDataSource` class.

You can also build your own servers or use an external service directly from proxy. The interface is defined in :py:mod:`aduser.proxy.client.ProviderClient`.

Packages
--------

.. toctree::
   :maxdepth: 1

   api_methods
   reference
   testing
   config
   deploy
   contributing

Indices and tables
==================

* :ref:`genindex`
* :ref:`modindex`
* :ref:`search`
