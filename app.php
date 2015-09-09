<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console;
use App\Cli;
use App\Model;

$app = new Console\Application;
try {
	$watcher = new Model\Watcher();
	$cmd = new Cli\Cmd($watcher);
	$app->add($cmd);
	$app->run();
} catch (Exception $e) {
	fputs(STDERR, $e->getMessage());
}
