<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console;
use Katzgrau\KLogger;
use App\Cli;
use App\Model;

$app = new Console\Application;
$logger = new KLogger\Logger(__DIR__ . '/tmp', \Psr\Log\LogLevel::DEBUG);
try {
	$watcher = new Model\Watcher($logger);
	$cmd = new Cli\Cmd($watcher);
	$app->add($cmd);
	$app->run();
} catch (\Exception $e) {
	$logger->error($e->getMessage() . "\n" . $e->getTraceAsString());
}
