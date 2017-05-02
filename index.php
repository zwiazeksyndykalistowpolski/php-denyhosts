<?php declare(strict_types=1);

// environment name (configuration file that will be used)
$envName = ($_SERVER['env'] ?? null) ?? ($_GET['env'] ?? 'default');
$envName = strtolower($envName);
$envName = preg_replace("/[^a-zA-Z0-9\\.\\-\\_]+/", "", $envName);
define('ENV_NAME', $envName);

if (!is_file(__DIR__ . '/configuration/config.' . $envName . '.php')) {
    print(json_encode([
        'error' => 'Invalid env parameter value',
    ], JSON_PRETTY_PRINT));
    exit;
}

require __DIR__ . '/vendor/autoload.php';
$logger = require __DIR__ . '/src/Bootstrap/Logger.php';
$config = require __DIR__ . '/configuration/config.' . $envName . '.php';

// If not header, then a parameter from query string, or just empty string
$token = ($_SERVER['HTTP_X_TOKEN'] ?? null) ?? ($_GET['_token'] ?? '');

if (PHP_SAPI !== 'cli' && $token !== ($config['token'] ?? '')) {
    header('HTTP/1.1 403 Forbidden');
    print(json_encode(['success' => false, 'message' => 'Invalid token']));
    exit;
}

$time = microtime(true);

$controller = new \PhpDenyhosts\ActionController($config, $logger);
$controller->cleanUpAction();

print(json_encode([
    'log' => $logHandler->getLogs(),
    'time' => microtime(true) - $time,
], JSON_PRETTY_PRINT) . "\n");