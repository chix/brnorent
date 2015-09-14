<?php

namespace App\Model;

use Nette\Utils;

class IsMuni implements ICrawler
{
	protected $config = null;
	/** @var \SimpleXMLElement[] */
	protected $xml = array();
	/** @var \DibiConnection */
	protected $dibi = null;

	public function __construct(\DibiConnection $dibi, array $config)
	{
		$this->dibi = $dibi;
		$this->config = $config;

		if (!isset($this->config['ismuni'])) {
			throw new \Exception("Could not init the IS muni crawler, config is missing.");
		}

		foreach ($config['ismuni']['url'] as $url) {
			$this->xml[] = simplexml_load_file($url);
		}
	}

	private function isNewAndMatchingPost($id, $title)
	{
		$this->dibi->query('CREATE TABLE IF NOT EXISTS posts (id TEXT)');
		$postExists = $this->dibi->query('SELECT * FROM posts WHERE id = ?', $id)->fetch();
		if ($postExists) {
			return false;
		}

		if (isset($this->config['ismuni']['exclude'])) {
			foreach($this->config['ismuni']['exclude'] as $excludingKeyword) {
				if (stristr(Utils\Strings::toAscii($title), $excludingKeyword) !== false) {
					return false;
				}
			}
		}

		return true;
	}

	public function getNewPosts()
	{
		$newPosts = array();

		foreach($this->xml as $xml) {
			foreach ($xml->xpath('//item') as $xmlElement) {
				$url = (string)$xmlElement->link;
				$title = (string)$xmlElement->title;
				if ($this->isNewAndMatchingPost($url, $title)) {
					$title = (string)$xmlElement->title;
					$description = (string)$xmlElement->description;
					$newPosts[] = array(
						'url' => $url,
						'title' => $title,
						'message' => $description
					);
					$this->dibi->query('INSERT INTO posts', array('id' => $url));
				}
			}
		}

		return $newPosts;
	}

}
