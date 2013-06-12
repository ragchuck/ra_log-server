<?php

require '../vendor/autoload.php';

$mode = array_key_exists('SLIM_MODE', $_ENV) ? $_ENV['SLIM_MODE'] : 'development';

// Instantiate a Slim application:
$app = new \Slim\Slim(require('../app/config/' .$mode . '.php'));


// Before Hooks
$app->hook('slim.before', function() use ($app) {
    // Set the default content-type
    $app->contentType('application/json');

    $db = $app->config('db');
    $db->debug = array($app->getLog(), 'debug');
});


// Define a HTTP GET route:
$app->get('/', function () use ($app) {
    $message = sprintf("I'm alive (mode=%s,debug=%s,log=%s)",
        $app->getMode(),
        $app->config('debug') ? 'true' : 'false',
        $app->getLog()->isEnabled() ? 'enabled' : 'disabled'
    );
    $app->render('json.php', array('result' => $message));
});

$app->get('/hello/:name', function ($name) use ($app) {
    $app->render('json.php', array('result' => "Hello, $name."));
});

$app->get('/test', function() use ($app) {
    $app->render('json.php', array('result' => $_SERVER));
});

$app->get('/phpinfo', function () use ($app) {
    phpinfo();
    $app->contentType('text/html');
});

$app->get('/charts', function () use ($app) {
    $db = $app->config('db');
    $charts = array();
    foreach($db->charts() as $chart) {
        $chart['options'] = json_decode($chart['options']);
        $charts[] = $chart->jsonSerialize();
    }
    $app->render('json.php', array('result' => $charts));
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
    
    $app->render('json.php', array('status' => $status, 'result' => $data));
    
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


//Run the Slim application:
$app->run();