<?php

namespace App\Model;

use Sunra\PhpSimple\HtmlDomParser;
use Katzgrau\KLogger;

class Idnes extends CrawlerBase implements CrawlerInterface
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

		$nodes = (array)$this->parser->find('div.nemo-info');
		if (empty($nodes)) {
			$this->logger->warning('Empty nodes array', array(__CLASS__));
		}
		foreach ($nodes as $node) {
			$url = 'http://reality.idnes.cz' . trim($node->find('h3 a', 0)->href);
			$title = iconv('windows-1250', 'utf-8', trim($node->find('h3 a', 0)->innertext));
			$price = iconv('windows-1250', 'utf-8', trim(strip_tags($node->parent()->parent()->find('td.price', 0)->innertext)));
			if (!$this->isNewAndMatchingPost($url, $title)) continue;

			$newPosts[] = array(
				'url' => $url,
				'title' => $title . ', ' . $price,
				'message' => ''
			);
		}

		return $newPosts;
	}

}
