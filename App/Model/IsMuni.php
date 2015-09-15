<?php

namespace App\Model;

class IsMuni implements CrawlerInterface
{
	use FilterTrait;

	protected $config = null;
	/** @var \SimpleXMLElement[] */
	protected $xml = array();
	/** @var \DibiConnection */
	protected $dibi = null;

	public function __construct(\DibiConnection $dibi, array $config)
	{
		$this->dibi = $dibi;
		$this->config = $config;

		foreach ($config['url'] as $url) {
			$this->xml[] = simplexml_load_file($url);
		}
	}

	public function getNewPosts()
	{
		$newPosts = array();

		foreach($this->xml as $xml) {
			foreach ($xml->xpath('//item') as $xmlElement) {
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
