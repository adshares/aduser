Welcome to AdUser's documentation!
==================================

What is AdUser?
---------------

Aduser is a data provider for AdServer. The data can be used for user targeting and fraud detection.


User Guide
==========

Goal of AdUser
--------------

Inform AdServer about consumers. The information is used in ad targeting and fraud detection.

Architecture
------------
AdUser is a Twisted app, backed by MongoDB and communicating via HTTP requests.

Python stack is as follows:

    * Twisted for the core network communication and asynchronous event handling
    * txmongo for asynchronous MongoDB communication

Data provider configuration
---------------------------
AdUser can use different data providers. Available data plugins can be found in `:py:mod:aduser.data`


Default consumer profile
------------------------
Default consumer profile has an empty keywords dictionary and a human score of 0.5.

Packages
--------

.. toctree::
   :maxdepth: 1

   api_methods
   reference
   config
   deploy
   testing
   contributing

Indices and tables
==================

* :ref:`genindex`
* :ref:`modindex`
* :ref:`search`
