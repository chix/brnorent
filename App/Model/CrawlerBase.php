<?php

namespace App\Model;

use Katzgrau\KLogger;

class CrawlerBase
{
	/** @var \DibiConnection */
	protected $dibi = null;
	/** @var KLogger\Logger */
	protected $logger = null;
	protected $config = null;

	public function __construct(\DibiConnection $dibi, KLogger\Logger $logger, array $config)
	{
		$this->dibi = $dibi;
		$this->logger = $logger;
		$this->config = $config;
	}

}
