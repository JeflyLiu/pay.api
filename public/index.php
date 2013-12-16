<?php
require __DIR__.'/../bootstrap/autoload.php';

// init app
$app = new \Slim\Slim($config);

require __DIR__.'/../bootstrap/database.php';
$app->db = $db;
$app->get('/', function() use ($app) {
 	echo 'Hello World !';
});

$app->group('/v1', function() use ($app) {

	// Get
	$app->get('/:resource(/(:method)(/(:params)))', function($resource, $method = null, $params = null) {
	    $resource = \App\Controller\Response::accessor('get',$resource, $method, $params);
	});

	// Post
	$app->post('/:resource(/(:method)(/(:params)))', function($resource, $method = null, $params = null) {
	    $resource = \App\Controller\Response::accessor('post',$resource, $method, $params);
	});

	// Put
	$app->put('/:resource/:id(/)', function($resource, $id = null) {
	    $resource = \App\Controller\Response::accessor(null,$resource, 'update', $id);
	});

	// Delete
	$app->delete('/:resource/:id(/)', function($resource, $id = null) {
	    $resource = \App\Controller\Response::accessor(null,$resource, 'destroy', $id);
	});

});

// Not found
$app->notFound(function() {
    App\Controller\Response::render(404);
});

$app->run();