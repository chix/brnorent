<?php

namespace App\Model;

use Nette\Mail;

class Watcher 
{
	protected $config = null;
	/** @var ICrawler[] */
	protected $crawlers = array();

	protected function init()
	{
		$config = parse_ini_file(__DIR__ . '/../../config.ini', true);
		if (!$config) {
			throw new \Exception("No config file found.");
		}
		$this->config = $config;

		$dibi = new \DibiConnection(array(
			'driver' => 'sqlite3',
			'database' => __DIR__ . '/../../tmp/db.sdb'
		));
		$dibi->query('CREATE TABLE IF NOT EXISTS posts (id TEXT)');

		$this->crawlers['facebook'] = new FacebookGroups($dibi, $config);
		$this->crawlers['bezrealitky'] = new Bezrealitky($dibi, $config);
	}

	public function run()
	{
		$this->init();

		$notification = '';
		foreach ($this->crawlers as $crawler) {
			foreach ($crawler->getNewPosts() as $post) {
				$notification .= sprintf('<h3><a href="%s">%s</a></h3><br>%s<br><br>', $post['url'], $post['title'], $post['message']);
			}
		}

		if ($notification) {
			$mailer = new Mail\SendmailMailer();
			$mail = new Mail\Message();
			foreach($this->config['app']['email_to'] as $email) {
				$mail->addTo($email);
			}
			$mail->setFrom($this->config['app']['email_from'])
				->setSubject('New rent offers')
				->setHtmlBody($notification);
			$mailer->send($mail);
		}
	}

}
