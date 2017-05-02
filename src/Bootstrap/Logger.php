<?php declare(strict_types=1);

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use PhpDenyhosts\Services\Logger\LogIntoArrayHandler;

$logHandler = new LogIntoArrayHandler(Logger::DEBUG);
$formatter = new LineFormatter("%datetime% > %level_name% > %message% %context% %extra%\n", "Y-m-d H:i:s");
return new Logger('php-denyhosts', [$logHandler]);
