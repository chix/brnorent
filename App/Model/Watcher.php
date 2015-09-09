<?php

namespace App\Model;

use Facebook;
use Nette\Utils;
use Nette\Mail;

class Watcher 
{
	protected $config = null;
	/** @var Facebook\Facebook */
	protected $facebook = null;
	protected $lastRunTimestamp = null;

	protected function init()
	{
		$config = parse_ini_file(__DIR__ . '/../../config.ini', true);

		if (!$config || !isset($config['facebook'])) {
			throw new \Exception("No config file found.");
		}

		$this->config = $config;

		$this->facebook = new Facebook\Facebook($config['facebook']);

		$pidFilePath = __DIR__ . '/../../tmp/last_run.pid';
		if (file_exists($pidFilePath)) {
			$this->lastRunTimestamp = intval(file_get_contents($pidFilePath));
		}
	}

	protected function isMatchingPost($post)
	{
		if (strtotime($post['updated_time']) < $this->lastRunTimestamp) {
			return false;
		}
			
		$title = Utils\Strings::toAscii(explode("\n", $post['message'])[0]);

		foreach($this->config['keywords']['exclude'] as $excludingKeyword) {
			if (stristr($title, $excludingKeyword) !== false) {
				return false;
			}
		}

		foreach($this->config['keywords']['include'] as $includingKeyword) {
			if (stristr($title, $includingKeyword) !== false) {
				return true;
			}
		}

		return false;
	}

	public function run()
	{
		$this->init();

		$matchingQueue = array();
		foreach($this->config['groups']['id'] as $groupId) {
			$response = $this->facebook->get(
				sprintf('/%s/feed', $groupId),
				$this->facebook->getApp()->getAccessToken()
			);
			foreach((array)$response->getDecodedBody()['data'] as $post) {
				if ($this->isMatchingPost($post)) {
					$matchingQueue[] = $post;
				}
			}
		}

		$mailer = new Mail\SendmailMailer();
		foreach($matchingQueue as $post) {
			$ids = explode('_', $post['id']);
			$link = sprintf('https://www.facebook.com/groups/%s/permalink/%s', $ids[0], $ids[1]);
			$title = explode("\n", $post['message'])[0];
			$mail = new Mail\Message();
			foreach($this->config['app']['email_to'] as $email) {
				$mail->addTo($email);
			}
			$mail->setFrom($this->config['app']['email_from'])
				->setSubject(Utils\Strings::toAscii($title))
				->setHtmlBody(sprintf('<a href="%s">%s</a>', $link, $title));
			$mailer->send($mail);
		}

		$h = fopen(__DIR__ . '/../../tmp/last_run.pid', 'w');
		fwrite($h, time());
		fclose($h);
	}

}
