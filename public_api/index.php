<?php

require '../vendor/autoload.php';

$config = require_once('../app/config/config.php');

// Instantiate a Slim application:
$app = new \Slim\Slim($config['slim']);

//Define a HTTP GET route:
$app->get('/', function () use ($app) {
    echo "Hello, World.";
});
$app->get('/hello/:name', function ($name) {
    echo "Hello, $name.";
});

//Run the Slim application:
$app->run();