<?php

return array(
	// Application
	'mode' => 'dev',
	// Debugging
	'debug' => true,
	// Logging
	'log.writer' => null,
	// 'log.writer' => new \App\Service\LogFile(array(
 //        'path' => APP_PATH . '/logs',
 //        'name_format' => 'Y-m-d',
 //        'extension' => 'log',
 //        'message_format' => '%label% - %date% - %message%'
 //    )),
	'log.level' => \Slim\Log::DEBUG,
	'log.enabled' => true,
	//database
	'db.driver' => array('default','haodingdan'),
	'db.connections' => array(
		'default' => array(
			'driver'    => 'mysql',
			'host'      => '192.168.1.211',
			'port'		=> '3306',
			'database'  => 'pay',
			'username'  => 'haodingdan',
			'password'  => '123456',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		),
		'haodingdan' => array(
			'driver'    => 'mysql',
			'host'      => '192.168.1.211',
			'port'		=> '3306',
			'database'  => 'haodingdan',
			'username'  => 'haodingdan',
			'password'  => '123456',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		),
	),
);