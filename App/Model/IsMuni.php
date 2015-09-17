<?php

namespace App\Model;

use Katzgrau\KLogger;

class IsMuni extends CrawlerBase implements CrawlerInterface
{
	use FilterTrait;

	/** @var \SimpleXMLElement[] */
	protected $xml = array();

	public function __construct(\DibiConnection $dibi, KLogger\Logger $logger, array $config)
	{
		parent::__construct($dibi, $logger, $config);

		foreach ($config['url'] as $url) {
			$this->xml[] = simplexml_load_file($url);
		}
	}

	public function getNewPosts()
	{
		$newPosts = array();

		foreach($this->xml as $xml) {
			$nodes = (array)$xml->xpath('//item');
			if (empty($nodes)) {
				$this->logger->warning('Empty nodes array', array(__CLASS__));
			}
			foreach ($nodes as $xmlElement) {
				$url = (string)$xmlElement->link;
				$title = (string)$xmlElement->title;
				$description = (string)$xmlElement->description;
				if (!$this->isNewAndMatchingPost($url, $title)) continue;

				$newPosts[] = array(
					'url' => $url,
					'title' => $title,
					'message' => $description
				);
			}
		}

		return $newPosts;
	}

}
