<?php

return array(
    'debug' => false,
    'templates.path' => '../app/templates/',
    'log.enabled' => true,
    'log.level' => \Slim\Log::WARN,
    'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
        'path' => '../app/logs/'
    ))
);