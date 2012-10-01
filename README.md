PHP Class for StatsD 
====================

Send statistics to the stats daemon over UDP.

This class generally follows the official php-example from Etsy, but moves the
configuration options into the main class with reasonable defaults.  This should
make incorporation into framework-based applications more straightforward.

Upstream:
https://github.com/etsy/statsd/blob/master/examples/php-example.php

License: MIT


Options
-------

`enabled` - boolean - Set to false to disable UDP transmission (default: true)

`prefix` - string - Apply a global namespace to all metrics from this application

`host` - string - Hostname or IP of your carbon/graphite server

`port` - integer - StatsD port of your carbon/graphite server


StatsD Data Types and Class Methods
-----------------

* Counting - `increment` and `decrement`

* Timing - `timing`

* Sampling - supported via "sampleRate" parameter on `increment` and `decrement`

* Gauges - `gauge`

* Sets - `set`



CakePHP v2 Installation
-----------------------

This class is generic enough that we can use it directly with CakePHP as as library.  The
installation instructions below assume that we want StatsD capability in all of
our controllers, but it could applied more precisely to only the required controllers,
models, or views if desired.

1) Copy the StatsD.php class into app/Lib

2) Make it available to all controllers by adding to the top of app/Controllers/AppController.php:

	App::uses('StatsD', 'Lib');

2) (Optional) Update app/Config/bootstrap.php if you want to use any of the configuration options:

	Configure::write('StatsD', array(
		'enabled' => true,
		'prefix' => 'your-app-name',
		'host' => 'your-carbon-server',
		'port' => XXXX
	));

3) (Optional) Add the following line to your beforeFilter in app/Controllers/AppController.php:

	StatsD::config(Configure::read('StatsD'));


CakePHP v2 Usage
----------------

Class methods are called statically.  Use the prefix option to keep your code clean and 
contain all of the metrics for the app within the same "bucket" in Graphite.

Example: Counting login actions (successful vs failed) in a UsersController:

	function login() {
	  ...
	  if (!$this->Auth->login()) {
	    StatsD::increment("logins.failed");
	    $this->Session->setFlash(__('Login failed.  Please try again'));
	    return;
	  }
	  StatsD::increment("logins.ok");
	  ...
	}

