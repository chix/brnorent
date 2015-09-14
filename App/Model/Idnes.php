<?php

namespace App\Model;

use Sunra\PhpSimple\HtmlDomParser;

class Idnes implements ICrawler
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

		if (!isset($this->config['idnes'])) {
			throw new \Exception("Could not init the idnes crawler, config is missing.");
		}

		$this->parser = HtmlDomParser::file_get_html($config['idnes']['url']);
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

		foreach ($this->parser->find('div.nemo-info') as $node) {
			$url = 'http://reality.idnes.cz' . trim($node->find('h3 a', 0)->href);
			if ($this->isNewPost($url)) {
				$title = iconv('windows-1250', 'utf-8', trim($node->find('h3 a', 0)->innertext));
				$price = iconv('windows-1250', 'utf-8', trim(strip_tags($node->parent()->parent()->find('td.price', 0)->innertext)));
				$newPosts[] = array(
					'url' => $url,
					'title' => $title . ', ' . $price,
					'message' => ''
				);
				$this->dibi->query('INSERT INTO posts', array('id' => $url));
			}
		}

		return $newPosts;
	}

}
