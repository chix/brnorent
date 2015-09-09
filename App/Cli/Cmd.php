<?php

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Cmd extends Command
{
	/** @var \App\Model\Watcher */
	protected $watcher = null;

	public function __construct(\App\Model\Watcher $watcher)
	{
		$this->watcher = $watcher;

		parent::__construct();
	}


	protected function configure()
	{
		$this->setName('watch')
			->setDescription('Find the latest posts since the last run and send a notification');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->watcher->run();
	}
}
