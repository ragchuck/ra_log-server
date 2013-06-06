<?php

return array(
    'slim' => array(
        'debug' => true,
        'log.enabled' => true,
        'log.level' => \Slim\Log::DEBUG,
        'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
            'path' => '../app/logs/'
        ))
    )
);