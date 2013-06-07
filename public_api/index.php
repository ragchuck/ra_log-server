<?php

require '../vendor/autoload.php';

// Instantiate a Slim application:
$app = new \Slim\Slim();

// Config modes
$app->configureMode('production', function() use ($app) {
    $app->config(require_once('../app/config/production.php'));
});
$app->configureMode('development', function() use ($app) {
    $app->config(require_once('../app/config/development.php'));
});

//Define a HTTP GET route:
$app->get('/', function () use ($app) {
    echo "I'm alive.";
});

$app->get('/hello/:name', function ($name) use ($app) {
    $app->render('json.php', array('data' => "Hello, $name."));
});

$app->get('/test', function() use ($app) {
    $app->render('json.php', array('data' => $app->request()->getContentType()));
});

$app->get(
        '/phpinfo(\.:format)', 
        function () use ($app) {
    phpinfo();
});

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
        'status' => 'NOT_FOUND',
        'error' => 'Page not found.'
    );
    echo json_encode($object);
    $app->contentType('application/json');
});

$app->hook('slim.after', function() use ($app) {
    //$app->response()->isOk()
    if ($app->response()->isOk()) {
        $app->response()->header('Content-type', $app->request()->getContentType());
    }
});

//Run the Slim application:
$app->run();