<?php

namespace App\Model;

use Sunra\PhpSimple\HtmlDomParser;
use Katzgrau\KLogger;

class Bazos extends CrawlerBase implements CrawlerInterface
{
	use FilterTrait;

	/** @var HtmlDomParser[] */
	protected $parsers = array();

	public function __construct(\DibiConnection $dibi, KLogger\Logger $logger, array $config)
	{
		parent::__construct($dibi, $logger, $config);

		foreach ($config['url'] as $url) {
			$this->parsers[] = HtmlDomParser::file_get_html($url);
		}
	}

	public function getNewPosts()
	{
		$newPosts = array();

		foreach ($this->parsers as $parser) {
			$nodes = (array)$parser->find('table.inzeraty');
			if (empty($nodes)) {
				$this->logger->warning('Empty nodes array', array(__CLASS__));
			}
			foreach ($nodes as $node) {
				$url = 'http://reality.bazos.cz' . trim($node->find('span.nadpis a', 0)->href);
				$title = trim($node->find('span.nadpis a', 0)->innertext);
				if (!$this->isNewAndMatchingPost($url, $title)) continue;

				$price = trim($node->find('span.cena b', 0)->innertext);
				$message = trim($node->find('div.popis', 0)->innertext);
				$newPosts[] = array(
					'url' => $url,
					'title' => $title . ', ' . $price,
					'message' => $message
				);
			}
		}

		return $newPosts;
	}

}
