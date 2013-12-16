<?php

return array(
	// Application
	'mode' => 'dev',
	// Debugging
	'debug' => true,
	// Logging
	'log.writer' => null,
	'log.level' => \Slim\Log::DEBUG,
	'log.enabled' => true,
	// View
	'templates.path' => APP_PATH.'/app/views',
	'view' => '\Slim\View',
	// Cookies
	'cookies.encrypt' => false,
	'cookies.lifetime' => '20 minutes',
	'cookies.path' => '/',
	'cookies.domain' => null,
	'cookies.secure' => false,
	'cookies.httponly' => false,
	// Encryption
	'cookies.secret_key' => 'CHANGE_ME',
	'cookies.cipher' => MCRYPT_RIJNDAEL_256,
	'cookies.cipher_mode' => MCRYPT_MODE_CBC,
	// HTTP
	'http.version' => '1.1',
	//controller
	'controller.class_prefix'    => '',
    'controller.method_suffix'   => '',
    'controller.template_suffix' => 'php',
    'controller.param_prefix'	 => '',
    'controller.cleanup_params'	 => true,
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