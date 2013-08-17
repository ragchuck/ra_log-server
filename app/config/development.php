<?php

return array(
    'debug' => true,
    'templates.path' => '../app/templates/',
    'import.path' => '../data/import/',
    'import.max_files' => 10,
    'log.enabled' => true,
    'log.level' => \Slim\Log::DEBUG,
    'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
        'path' => '../app/logs/'
    )),
    'DB' => new PDO('mysql:host=localhost;dbname=ra_log', 'ra_log-user', 'ra_log-user')
);