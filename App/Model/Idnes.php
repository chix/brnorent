<?php

namespace App\Model;

use Sunra\PhpSimple\HtmlDomParser;

class Idnes implements CrawlerInterface
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

		foreach ($this->parser->find('div.nemo-info') as $node) {
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
