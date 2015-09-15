<?php

namespace App\Model;

use Nette\Utils;

trait FilterTrait
{
	public function isNewAndMatchingPost($url, $title = null)
	{
		$this->dibi->query('CREATE TABLE IF NOT EXISTS posts (id TEXT)');
		$postExists = $this->dibi->query('SELECT * FROM posts WHERE id = ?', $url)->fetch();
		if ($postExists) return false;
			
		if ($title && isset($this->config['exclude'])) {
			$title = Utils\Strings::toAscii($title);
			foreach($this->config['exclude'] as $excludingKeyword) {
				if (stristr($title, $excludingKeyword) !== false) {
					return false;
				}
			}
		}

		return true;
	}
}
