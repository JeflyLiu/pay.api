<?php

return array(
	// Application
	'mode' => 'qa',
	// Debugging
	'debug' => true,
	// Logging
	'log.enabled' => true,
	//database
	'db.driver' => 'mysql',
	'db.connections' => array(
		'mysql' => array(
			'driver'    => 'mysql',
			'host'      => '192.168.1.127',
			'port'		=> '3306',
			'database'  => 'pay',
			'username'  => 'haodingdan',
			'password'  => '123456',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		),
	),
);