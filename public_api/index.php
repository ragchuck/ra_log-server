<?php

require '../vendor/autoload.php';

// Instantiate a Slim application:
$app = new \Slim\Slim();

// Config modes
$app->configureMode('production', function() use ($app) {
    $app->config(require('../app/config/production.php'));
});
$app->configureMode('development', function() use ($app) {
    $app->config(require('../app/config/development.php'));
});

//Define a HTTP GET route:
$app->get('/', function () use ($app) {
    echo "I'm alive.";
});

$app->get('/hello/:name', function ($name) use ($app) {
    $app->render('json.php', array('data' => "Hello, $name."));
});

$app->get('/test', function() use ($app) {
    $app->render('json.php', array('data' => $_SERVER));
});

$app->get('/phpinfo(\.:format)', function () {
    phpinfo();
    $app->contentType('text/html');
});

$app->map('/:controller(/:action)', function ($controllerClass, $action = 'index') use ($app) {
    
    $controllerClass = 'Ralog\\Controller\\' . $controllerClass;
    
    if (!class_exists($controllerClass))
        $app->notFound ();
    
    $controller = new $controllerClass($app);
    
    $method = strtolower($app->request()->getMethod() . '_' . $action);
    
    if (!method_exists($controller, $method))
        $app->notFound ();
    
    list($status, $data) = $controller->$method($app->request()->params());
    
    $app->render('json.php', array('status' => $status, 'data' => $data));
    
})->name('controller-action')->via('GET','POST','DELETE','PUT');


$app->error(function (\Exception $e) use ($app) {
    $object = array(
        'status' => 'ERROR',
        'error' => $e->getMessage()
    );
    $app->response()->body(json_encode($object));
    $app->response()->header('Content-type', 'application/json');
});

$app->notFound(function () use ($app) {
    $object = array(
        'status' => 'NOT_FOUND'
    );
    echo json_encode($object);
    $app->contentType('application/json');
});

$app->hook('slim.after', function() use ($app) {
    $app->contentType('application/json');
});

//Run the Slim application:
$app->run();