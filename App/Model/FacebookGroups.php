<?php

namespace App\Model;

use Facebook;
use Nette\Utils;

class FacebookGroups implements ICrawler
{
	protected $config = null;
	/** @var Facebook\Facebook */
	protected $facebook = null;
	/** @var \DibiConnection */
	protected $dibi = null;

	public function __construct(\DibiConnection $dibi, array $config)
	{
		$this->dibi = $dibi;
		$this->config = $config;

		if (!isset($this->config['facebook'])) {
			throw new \Exception("Could not init the facebook crawler, config is missing.");
		}

		$this->facebook = new Facebook\Facebook(array(
			'app_id' => $this->config['facebook']['app_id'],
			'app_secret' => $this->config['facebook']['app_secret'],
			'default_graph_version' => 'v2.4'
		));
	}

	private function isNewAndMatchingPost($post)
	{
		$this->dibi->query('CREATE TABLE IF NOT EXISTS posts (id TEXT)');
		$postExists = $this->dibi->query('SELECT * FROM posts WHERE id = ?', $post['id'])->fetch();
		if ($postExists || !isset($post['message'])) {
			return false;
		}
			
		if (isset($this->config['facebook']['exclude']) && $this->config['facebook']['include']) {
			$title = Utils\Strings::toAscii(explode("\n", $post['message'])[0]);

			foreach($this->config['facebook']['exclude'] as $excludingKeyword) {
				if (stristr($title, $excludingKeyword) !== false) {
					return false;
				}
			}

			foreach($this->config['facebook']['include'] as $includingKeyword) {
				if (stristr($title, $includingKeyword) !== false) {
					return true;
				}
			}

			return false;
		} else {
			return true;
		}
	}

	public function getNewPosts()
	{
		$newPosts = array();

		foreach($this->config['facebook']['group_id'] as $groupId) {
			$response = $this->facebook->get(
				sprintf('/%s/feed', $groupId),
				$this->facebook->getApp()->getAccessToken()
			);
			foreach((array)$response->getDecodedBody()['data'] as $post) {
				if ($this->isNewAndMatchingPost($post)) {
					$ids = explode('_', $post['id']);
					$messageLines = explode("\n", $post['message']);
					$title = array_shift($messageLines);
					$newPosts[] = array(
						'url' => sprintf('https://www.facebook.com/groups/%s/permalink/%s', $ids[0], $ids[1]),
						'title' => $title,
						'message' => implode('<br>', $messageLines)
					);

					$this->dibi->query('INSERT INTO posts', array('id' => $post['id']));
				}
			}
		}

		return $newPosts;
	}

}
