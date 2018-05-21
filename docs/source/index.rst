Welcome to AdUser's documentation!
==================================

What is AdUser?
---------------

AdUser is an element in Adshares network. It provides consumer information to the network, through AdServer. Two most important consumer information are:

    * human score - likelihood of this user being a real human and not a bot
    * keywords - set of keywords describing the consumer, eg. age, country and interests.

Deployment
==========

Installation
------------

Full installation instructions can be found in `README.md <https://github.com/adshares/aduser/blob/master/README.md>`_. AdUser is run within a Virtualenv. Dependencies are provided in requirements.txt and you can use pip to install them.

Make sure you set up the ``$ADUSER_ROOT`` environment variable to point to the root directory of AdUser - the directory containing the aduser package.

Configuration
-------------

Configuration is spread among 5 files:

    * aduser.proxy.const
    * aduser.simple_provider.client.const
    * aduser.simple_provider.server.const
    * config/log_config.json
    * config/supervisord.conf

AdUser logging config
^^^^^^^^^^^^^^^^^^^^^

*config/log_config.json* contains Python logging configuration. You can learn more about it `here. <https://docs.python.org/2/library/logging.config.html>`_ The AdUser daemon will look for this file in the ``$ADUSER_ROOT/aduser/config`` directory, where ``$ADUSER_ROOT`` is an environmental variable.

AdUser proxy configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^

*aduser.proxy.const* is a python file containing configuration for proxy side of AdUser.

AdUser simple provider client configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

*aduser.simple_provider.client.const* is a python file containing configuration for client side of AdUser.

AdUser simple provider server configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

*aduser.simple_provider.server.const* is a python file containing configuration for server side of AdUser.

.. NOTE::
    Configuration items you should specially consider in initial deployment:

    * Tracking cookie name
    * Tracking id expiration

Supervisor config
^^^^^^^^^^^^^^^^^

Config for supervisor daemon configuration (log and pid file paths) is in *config/supervisord.conf*.

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
    * pygelf for Graylog integration
    * python-geoip for MaxMind GeoIP integration
    * pybrowscap for Browscap integration
    * supervisor for running it as a daemon

AdUser is conceptually divided into two parts:

    * Proxy
    * Data server

The proxy accepts requests from AdServer and requests information from the data server. It also hosts the cache and attaches the tracking cookie. The server provides the information about the consumer.

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

Testing
-------

For testing you'll need additional libraries (mock and mongomock). Tests can be run using Twisted Trial.

    ``trial tests``

To test with a live MongoDB instance, run the tests without the mongomock library.

    ``trial tests --without mongomock``

Packages
--------

.. toctree::
   :maxdepth: 1

   modules
   aduser
   aduser.proxy
   aduser.simple_provider
   aduser.simple_provider.client
   aduser.simple_provider.server
   aduser.simple_provider.server.data_sources

Indices and tables
==================

* :ref:`genindex`
* :ref:`modindex`
* :ref:`search`
