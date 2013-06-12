<?php

return array(
    'debug' => false,
    'templates.path' => '../app/templates/',
    'log.enabled' => true,
    'log.level' => \Slim\Log::WARN,
    'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
        'path' => '../app/logs/'
    )),
    'db' => new NotORM(
        new PDO('mysql:host=localhost;dbname=ra_log', 'ra_log-user', 'ra_log-user'),
        new NotORM_Structure_Convention()
    //new NotORM_Cache_Include(sys_get_temp_dir() . 'notorm')
    )
);