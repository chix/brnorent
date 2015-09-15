<?php

namespace App\Model;

class Sreality implements CrawlerInterface
{
	use FilterTrait;

	protected $config = null;
	/** @var \SimpleXMLElement */
	protected $xml = null;
	/** @var \DibiConnection */
	protected $dibi = null;

	public function __construct(\DibiConnection $dibi, array $config)
	{
		$this->dibi = $dibi;
		$this->config = $config;

		$this->xml = simplexml_load_file($config['url']);
	}

	public function getNewPosts()
	{
		$newPosts = array();

		foreach ($this->xml->xpath('//item') as $xmlElement) {
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
