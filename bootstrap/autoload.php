<?php

date_default_timezone_set('Asia/Shanghai');

define('M', microtime(true));
define('APP_PATH', dirname(__DIR__));

require APP_PATH . '/vendor/autoload.php';
require APP_PATH . '/bootstrap/helpers.php';
require APP_PATH . '/vendor/symfony/class-loader/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces(array(
	'App' => APP_PATH . '/src'
));
$loader->register();
$config = require APP_PATH . '/config/app.php';

//cli表示以控制台脚本形式运行，web下为cgi形式，cgi的php_sapi_name()则多种多样了，但是都基本包含'cgi'这个字符串
if(php_sapi_name() != 'cli'){
	if(isset($_SERVER['HTTP_HOST'])){
		//Configuration
		$_ENV['SLIM_MODE'] = detectEnvironment($_SERVER['HTTP_HOST'],array(
		
			'dev'		=> array('localhost','127.0.0.1','*.dev'),
			'qa'		=> array('192.168.1.127','*.qa'),
			'stage'		=> array('192.168.1.125','*.staging'),
			'live'		=> array('192.168.1.240','*.com'),
		
		));
		
		if ($_ENV['SLIM_MODE'] and file_exists($dir = APP_PATH.'/config/'.$_ENV['SLIM_MODE'].'/app.php'))
		{
			$config = array_merge($config, require $dir);
		}
	}
}


