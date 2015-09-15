<?php

namespace App\Model;

use Facebook;

class FacebookGroups implements CrawlerInterface
{
	use FilterTrait;

	protected $config = null;
	/** @var Facebook\Facebook */
	protected $facebook = null;
	/** @var \DibiConnection */
	protected $dibi = null;

	public function __construct(\DibiConnection $dibi, array $config)
	{
		$this->dibi = $dibi;
		$this->config = $config;

		$this->facebook = new Facebook\Facebook(array(
			'app_id' => $this->config['app_id'],
			'app_secret' => $this->config['app_secret'],
			'default_graph_version' => 'v2.4'
		));
	}

	public function getNewPosts()
	{
		$newPosts = array();

		foreach($this->config['group_id'] as $groupId) {
			$response = $this->facebook->get(
				sprintf('/%s/feed', $groupId),
				$this->facebook->getApp()->getAccessToken()
			);
			foreach((array)$response->getDecodedBody()['data'] as $post) {
				if (!isset($post['message'])) continue;
				$ids = explode('_', $post['id']);
				$url = sprintf('https://www.facebook.com/groups/%s/permalink/%s', $ids[0], $ids[1]);
				$messageLines = explode("\n", $post['message']);
				$title = array_shift($messageLines);
				$message = implode('<br>', $messageLines);
				if (!$this->isNewAndMatchingPost($url, $title)) continue;

				$newPosts[] = array(
					'url' => $url,
					'title' => $title,
					'message' => $message
				);
			}
		}

		return $newPosts;
	}

}
