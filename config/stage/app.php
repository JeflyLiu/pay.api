<?php

return array(
	// Application
	'mode' => 'staging',
	// Debugging
	'debug' => false,
	// Logging
	'log.enabled' => false,
	//database
	'db.driver' => 'mysql',
	'db.connections' => array(
		'mysql' => array(
			'driver'    => 'mysql',
			'host'      => '192.168.1.125',
			'port'		=> '3306',
			'database'  => 'pay',
			'username'  => 'haodingdan',
			'password'  => 'stagingdb',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		),
	),
);