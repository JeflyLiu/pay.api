<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

$driver = $config['db.driver'];
$connections = $config['db.connections'];

$db = new Capsule;

foreach ((array)$driver as $name){
	if (isset($connections[$name])) {
		$db->addConnection($connections[$name], $name);
	}
}

// Set the event dispatcher used by Eloquent models... (optional)
// $app->db->setEventDispatcher(new Dispatcher(new Container));

// Set the cache manager instance used by connections... (optional)
// $capsule->setCacheManager(...);

// Make this Capsule instance available globally via static methods... (optional)
$db->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$db->bootEloquent();

