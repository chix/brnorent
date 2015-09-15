<?php

namespace App\Model;

use Sunra\PhpSimple\HtmlDomParser;

class Bezrealitky implements CrawlerInterface
{
	use FilterTrait;

	protected $config = null;
	/** @var HtmlDomParser */
	protected $parser = null;
	/** @var \DibiConnection */
	protected $dibi = null;

	public function __construct(\DibiConnection $dibi, array $config)
	{
		$this->dibi = $dibi;
		$this->config = $config;

		$this->parser = HtmlDomParser::file_get_html($config['url']);
	}

	public function getNewPosts()
	{
		$newPosts = array();

		foreach ($this->parser->find('div.list div.record div.details') as $node) {
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
