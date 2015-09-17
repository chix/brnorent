<?php

namespace App\Model;

use Sunra\PhpSimple\HtmlDomParser;
use Katzgrau\KLogger;

class Bezrealitky extends CrawlerBase implements CrawlerInterface
{
	use FilterTrait;

	/** @var HtmlDomParser */
	protected $parser = null;

	public function __construct(\DibiConnection $dibi, KLogger\Logger $logger, array $config)
	{
		parent::__construct($dibi, $logger, $config);

		$this->parser = HtmlDomParser::file_get_html($config['url']);
	}

	public function getNewPosts()
	{
		$newPosts = array();

		$nodes = (array)$this->parser->find('div.list div.record div.details');
		if (empty($nodes)) {
			$this->logger->warning('Empty nodes array', array(__CLASS__));
		}
		foreach ($nodes as $node) {
			$url = trim($node->find('p.short-url', 0)->innertext);
			$iconToRemove = trim($node->find('h2 a i', 0)->outertext);
			$address = trim(str_replace($iconToRemove, '', $node->find('h2 a', 0)->innertext));
			$keywords = trim($node->find('p.keys', 0)->innertext);
			$price = trim($node->find('p.price', 0)->innertext);
			$title = $keywords . ', ' . $address . ', ' . $price;
			$message = trim($node->find('p.description', 0)->innertext);
			if (!$this->isNewAndMatchingPost($url, $title)) continue;

			$newPosts[] = array(
				'url' => $url,
				'title' => $title,
				'message' => $message
			);
		}

		return $newPosts;
	}

}
