<?php declare(strict_types=1);

if (!is_file(__DIR__ . '/config.php')) {
    print('Application not configured, please create the config.php');
    exit;
}

require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

$controller = new \PhpDenyhosts\ActionController($config);
$controller->cleanUpAction();
