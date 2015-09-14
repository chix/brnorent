<?php

namespace App\Model;

use Sunra\PhpSimple\HtmlDomParser;

class Bazos implements ICrawler
{
	protected $config = null;
	/** @var HtmlDomParser[] */
	protected $parsers = array();
	/** @var \DibiConnection */
	protected $dibi = null;

	public function __construct(\DibiConnection $dibi, array $config)
	{
		$this->dibi = $dibi;
		$this->config = $config;

		if (!isset($this->config['bazos'])) {
			throw new \Exception("Could not init the bezrealitky crawler, config is missing.");
		}

		foreach ($config['bazos']['url'] as $url) {
			$this->parsers[] = HtmlDomParser::file_get_html($url);
		}
	}

	private function isNewPost($id)
	{
		$this->dibi->query('CREATE TABLE IF NOT EXISTS posts (id TEXT)');
		$postExists = $this->dibi->query('SELECT * FROM posts WHERE id = ?', $id)->fetch();
		if ($postExists) {
			return false;
		}

		return true;
	}

	public function getNewPosts()
	{
		$newPosts = array();

		foreach ($this->parsers as $parser) {
			foreach ($parser->find('table.inzeraty') as $node) {
				$url = 'http://reality.bazos.cz' . trim($node->find('span.nadpis a', 0)->href);
				if ($this->isNewPost($url)) {
					$title = trim($node->find('span.nadpis a', 0)->innertext);
					$price = trim($node->find('span.cena b', 0)->innertext);
					$message = trim($node->find('div.popis', 0)->innertext);
					$newPosts[] = array(
						'url' => $url,
						'title' => $title . ', ' . $price,
						'message' => $message
					);
					$this->dibi->query('INSERT INTO posts', array('id' => $url));
				}
			}
		}

		return $newPosts;
	}

}
