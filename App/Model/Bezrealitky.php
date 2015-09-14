<?php

namespace App\Model;

use Sunra\PhpSimple\HtmlDomParser;

class Bezrealitky implements ICrawler
{
	protected $config = null;
	/** @var HtmlDomParser */
	protected $parser = null;
	/** @var \DibiConnection */
	protected $dibi = null;

	public function __construct(\DibiConnection $dibi, array $config)
	{
		$this->dibi = $dibi;
		$this->config = $config;

		if (!isset($this->config['bezrealitky'])) {
			throw new \Exception("Could not init the bezrealitky crawler, config is missing.");
		}

		$this->parser = HtmlDomParser::file_get_html($config['bezrealitky']['url']);
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

		foreach ($this->parser->find('div.list div.record div.details') as $node) {
			$url = trim($node->find('p.short-url', 0)->innertext);
			if ($this->isNewPost($url)) {
				$iconToRemove = trim($node->find('h2 a i', 0)->outertext);
				$address = trim(str_replace($iconToRemove, '', $node->find('h2 a', 0)->innertext));
				$keywords = trim($node->find('p.keys', 0)->innertext);
				$price = trim($node->find('p.price', 0)->innertext);
				$message = trim($node->find('p.description', 0)->innertext);
				$newPosts[] = array(
					'url' => $url,
					'title' => $keywords . ', ' . $address . ', ' . $price,
					'message' => $message
				);
				$this->dibi->query('INSERT INTO posts', array('id' => $url));
			}
		}

		return $newPosts;
	}

}
