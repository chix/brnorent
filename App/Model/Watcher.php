<?php

namespace App\Model;

use Facebook;

class Watcher 
{
	protected $config = null;
	/** @var Facebook\Facebook */
	protected $facebook = null;
	protected $lastRunTimestamp = null;

	protected function init()
	{
		$config = parse_ini_file(__DIR__ . '/../../config.ini', true);

		if (!$config || !isset($config['facebook'])) {
			throw new \Exception("No config file found.");
		}

		$this->config = $config;

		$this->facebook = new Facebook\Facebook($config['facebook']);

		$pidFilePath = __DIR__ . '/../../tmp/last_run.pid';
		if (file_exists($pidFilePath)) {
			$this->lastRunTimestamp = intval(file_get_contents($pidFilePath));
		}
	}

	public function run()
	{
		$this->init();

		$h = fopen(__DIR__ . '/../../tmp/last_run.pid', 'w');
		fwrite($h, time());
		fclose($h);
	}

}
