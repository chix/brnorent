<?php

namespace App\Model;

use Facebook;
use Katzgrau\KLogger;

class FacebookGroups extends CrawlerBase implements CrawlerInterface
{
	use FilterTrait;

	/** @var Facebook\Facebook */
	protected $facebook = null;

	public function __construct(\DibiConnection $dibi, KLogger\Logger $logger, array $config)
	{
		parent::__construct($dibi, $logger, $config);

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
			$posts = array();
			try {
				$response = $this->facebook->get(
					sprintf('/%s/feed', $groupId),
					$this->facebook->getApp()->getAccessToken()
				);
				$posts += (array)$response->getDecodedBody()['data'];
			} catch (\Exception $e) {
				$this->logger->warning($e->getMessage(), array(__CLASS__));
			}

			foreach($posts as $post) {
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
