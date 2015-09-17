<?php

namespace App\Model;

use Katzgrau\KLogger;

class Sreality extends CrawlerBase implements CrawlerInterface
{
	use FilterTrait;

	/** @var \SimpleXMLElement */
	protected $xml = null;

	public function __construct(\DibiConnection $dibi, KLogger\Logger $logger, array $config)
	{
		parent::__construct($dibi, $logger, $config);

		$this->xml = simplexml_load_file($config['url']);
	}

	public function getNewPosts()
	{
		$newPosts = array();

		$nodes = (array)$this->xml->xpath('//item');
		if (empty($nodes)) {
			$this->logger->warning('Empty nodes array', array(__CLASS__));
		}
		foreach ($nodes as $xmlElement) {
			$url = (string)$xmlElement->link;
			$title = (string)$xmlElement->title . ', ' . (string)$xmlElement->description;
			if (!$this->isNewAndMatchingPost($url, $title)) continue;

			$newPosts[] = array(
				'url' => $url,
				'title' => $title,
				'message' => ''
			);
		}

		return $newPosts;
	}

}
