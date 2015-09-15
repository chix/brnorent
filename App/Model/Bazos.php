<?php

namespace App\Model;

use Sunra\PhpSimple\HtmlDomParser;

class Bazos implements CrawlerInterface
{
	use FilterTrait;

	protected $config = null;
	/** @var HtmlDomParser[] */
	protected $parsers = array();
	/** @var \DibiConnection */
	protected $dibi = null;

	public function __construct(\DibiConnection $dibi, array $config)
	{
		$this->dibi = $dibi;
		$this->config = $config;

		foreach ($config['url'] as $url) {
			$this->parsers[] = HtmlDomParser::file_get_html($url);
		}
	}

	public function getNewPosts()
	{
		$newPosts = array();

		foreach ($this->parsers as $parser) {
			foreach ($parser->find('table.inzeraty') as $node) {
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
