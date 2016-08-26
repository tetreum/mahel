<?php
define('APP_ROOT', __DIR__ . DIRECTORY_SEPARATOR);

date_default_timezone_set("Europe/Madrid");

return [
    'tmp' => APP_ROOT . 'tmp/',
    'mode' => 'production',
    'debug' => false,
    'test' => false,
    'providers' => [
        'Pepecine',
    ]
];