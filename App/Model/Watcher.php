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
		$this->crawlers['bazos'] = new Bazos($dibi, $config);
		$this->crawlers['sreality'] = new Sreality($dibi, $config);
		$this->crawlers['idnes'] = new Idnes($dibi, $config);
		$this->crawlers['ismuni'] = new IsMuni($dibi, $config);
	}

	public function run()
	{
		$this->init();

		$count = 0;
		$notification = '';
		foreach ($this->crawlers as $crawler) {
			foreach ($crawler->getNewPosts() as $post) {
				$notification .= sprintf('<h3><a href="%s">%s</a></h3><br>%s<br><br>', $post['url'], $post['title'], $post['message']);
				$count += 1;
			}
		}

		if ($count) {
			$mailer = new Mail\SendmailMailer();
			$mail = new Mail\Message();
			foreach($this->config['app']['email_to'] as $email) {
				$mail->addTo($email);
			}
			$mail->setFrom($this->config['app']['email_from'])
				->setSubject(sprintf('%d new rent offer(s)', $count))
				->setHtmlBody($notification);
			$mailer->send($mail);
		}
	}

}
