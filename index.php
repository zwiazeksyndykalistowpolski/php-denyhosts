<?php declare(strict_types=1);

require __DIR__ . '/src/Bootstrap/bootstrap.php';

$time = microtime(true);

$controller = new \PhpDenyhosts\ActionController($config, $logger);
$controller->cleanUpAction();

print(json_encode([
    'log' => $logHandler->getLogs(),
    'time' => microtime(true) - $time,
], JSON_PRETTY_PRINT) . "\n");
