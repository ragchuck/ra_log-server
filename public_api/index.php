<?php

require '../vendor/autoload.php';

$mode = array_key_exists('SLIM_MODE', $_ENV) ? $_ENV['SLIM_MODE'] : 'development';

// Instantiate a Slim application:
$app = new \Slim\Slim(require('../app/config/' . $mode . '.php'));


// Before Hooks
$app->hook('slim.before', function() use ($app) {
    // Set the default content-type
    $app->contentType('application/json');


    $app->db = new NotORM(
        $app->config('DB'),
        new NotORM_Structure_Convention()
        //new NotORM_Cache_Include(sys_get_temp_dir() . 'notorm')
    );
    $app->db->debug = array($app->getLog(), 'debug');
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



$app->get('/chart', function () use ($app) {
    $db = $app->db;

    $charts = array();

    foreach($db->charts() as $chart) {
        $chart['options'] = json_decode($chart['options']);
        $chart['series'] = json_decode($chart['series']);
        $charts[] = $chart->jsonSerialize();
    }

    if(empty($charts))
        $app->notFound();

    $app->render('json.php', array('result' => $charts));

});



$app->get('/chart(/:name)', function ($name) use ($app) {
    $db = $app->db;

    $chart = $db->charts()->where(array('name' => $name))->fetch();

    if(!$chart)
        $app->notFound();

    $chart['options'] = json_decode($chart['options']);
    $chart['series'] = json_decode($chart['series']);

    $app->render('json.php', array('result' => $chart->jsonSerialize()));

});



$app->get('/data/:view', function($view) use ($app) {
    $viewName = "v_" . strtolower($view);
    $db = $app->db;
    $result = $db->$viewName()->where($app->request()->params());
    $data = array();
    foreach($result as $row) {
        $data[] = $row->jsonSerialize();
    }
    $app->render('json.php', array('result' => $data));
});



$app->get('/table', function() use ($app) {
    $db = $app->db;
    $r = $app->request();
    $year = $r->params('year');
    $month = $r->params('month');
    $day = $r->params('day');
    $vDataSum = $db->v_data_sum();

    if($year)
        $vDataSum->where("year IS null OR year = ?", str_pad($year, 4, "0", STR_PAD_LEFT));
    else
        $vDataSum->where("year IS null");

    if($month)
        $vDataSum->where("month IS null OR month = ?", str_pad($month, 2, "0", STR_PAD_LEFT));
    else
        $vDataSum->where("month IS null");

    if($day)
        $vDataSum->where("day IS null OR day = ?", str_pad($day, 2, "0", STR_PAD_LEFT));
    else
        $vDataSum->where("day IS null");

    $data = array();
    foreach($vDataSum as $sum) {
        if(is_null($sum['year']))
            $sum['title'] = 'SUM_TOTAL';
        elseif(is_null($sum['month']))
            $sum['title'] = 'SUM_YEAR';
        elseif(is_null($sum['day']))
            $sum['title'] = 'SUM_MONTH';
        else
            $sum['title'] = 'SUM_DAY';
        $data[] = $sum->jsonSerialize();
    }
    $app->render('json.php', array('result' => $data));
});



$app->get('/import/files', function () use ($app) {

    $importPath = __DIR__ . '/' . $app->config('import.path');
    $importMaxFiles = $app->config('import.max_files');

    \Ralog\Import::setPath($importPath);

    $files = \Ralog\Import::find_files($importMaxFiles);

    $app->render('json.php', array('result' => $files));
});



$app->post('/import/file', function () use ($app) {

    // Get the file name from request body (JSON)
    $request = json_decode($app->request()->getBody(), true);

    if (!isset($request['file']))
        $app->error(new \Exception('FILE parameter is missing.'));

    $file = $request['file'];
    $importPath = $app->config('import.path');
    $importRootPath = __DIR__ . DIRECTORY_SEPARATOR . $importPath;

    \Ralog\Import::setPath($importRootPath);

    $result = \Ralog\Import::import($file);

    $app->render('json.php', array('result' => $result));
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