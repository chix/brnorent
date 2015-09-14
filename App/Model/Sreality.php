<?php

namespace App\Model;

class Sreality implements ICrawler
{
	protected $config = null;
	/** @var \SimpleXMLElement */
	protected $xml = null;
	/** @var \DibiConnection */
	protected $dibi = null;

	public function __construct(\DibiConnection $dibi, array $config)
	{
		$this->dibi = $dibi;
		$this->config = $config;

		if (!isset($this->config['sreality'])) {
			throw new \Exception("Could not init the sreality crawler, config is missing.");
		}

		$this->xml = simplexml_load_file($config['sreality']['url']);
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

		foreach ($this->xml->xpath('//item') as $xmlElement) {
			$url = (string)$xmlElement->link;
			if ($this->isNewPost($url)) {
				$title = (string)$xmlElement->title;
				$description = (string)$xmlElement->description;
				$newPosts[] = array(
					'url' => $url,
					'title' => $title . ', ' . $description,
					'message' => ''
				);
				$this->dibi->query('INSERT INTO posts', array('id' => $url));
			}
		}

		return $newPosts;
	}

}
