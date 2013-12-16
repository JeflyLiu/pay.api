<?php

return array(
	// Debugging
	'debug' => false,
	// Logging
	'log.enabled' => false,
	//database
	'db.driver' => array('default','haodingdan'),
	'db.connections' => array(
		'default' => array(
			'driver'    => 'mysql',
			'host'      => '192.168.1.240',
			'port'		=> '3306',
			'database'  => 'haodingdan',
			'username'  => 'haodingdan',
			'password'  => 'Hdd_jt#$%2010',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		),
		'haodingdan' => array(
			'driver'    => 'mysql',
			'host'      => '192.168.1.240',
			'port'		=> '3306',
			'database'  => 'pay',
			'username'  => 'haodingdan',
			'password'  => 'Hdd_jt#$%2010',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		),
	),
);