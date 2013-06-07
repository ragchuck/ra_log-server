<?php

return array(
    'slim' => array(
        'debug' => true,
        'templates.path' => '../app/templates/',
        'log.enabled' => true,
        'log.level' => \Slim\Log::DEBUG,
        'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
            'path' => '../app/logs/'
        ))
    )
);