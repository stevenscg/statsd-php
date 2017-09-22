PHP Class for StatsD
====================

Send statistics to the statsd daemon over UDP.

This class generally follows the official php-example from Etsy, but moves the
configuration options into the main class with reasonable defaults.  This should
make incorporation into framework-based applications more straightforward.


Installation
------------

Installation is via composer:
```
composer require stevenscg/statsd-php
```


Configuration
-------------

The library can be configured by calling `StatsD::config($params)`.

`$params` is an array that supports the following keys:

`enabled` - boolean - Set to false to disable UDP transmission (default: true)

`prefix` - string - Apply a global namespace to all metrics from this application

`host` - string - Hostname or IP of your carbon/graphite server

`port` - integer - StatsD port of your carbon/graphite server


The library can also be configured via environment variables:

`STATSD_ENABLED` - A boolean-like string (i.e. true, false, 1, 0)

`STATSD_PREFIX` - string

`STATSD_ADDR` - string - Example: 127.0.0.1:8125


StatsD Data Types and Class Methods
-----------------

* Counting - `increment` and `decrement`

* Timing - `timing`

* Sampling - supported via "sampleRate" parameter on `increment` and `decrement`

* Gauges - `gauge`

* Sets - `set`


Usage
-----

All methods are declared as static as they were in the upstream project.

Incrementing a counter is as simple as:
```
StatsD::increment("api.requests");
```


Example: Tracking logins and failures

    function login() {
      ...
      if (!$this->Auth->login()) {
        StatsD::increment("logins.failed");
        return;
      }
      StatsD::increment("logins.ok");
      ...
    }


License
-------

MIT
