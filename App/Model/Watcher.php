<?php

namespace App\Model;

use Nette\Mail;

class Watcher 
{
	use FilterTrait;

	protected $config = null;
	/** @var \DibiConnection */
	protected $dibi = null;
	/** @var ICrawler[] */
	protected $crawlers = array();

	protected function init()
	{
		$config = parse_ini_file(__DIR__ . '/../../config.ini', true);
		if (!$config) {
			throw new \Exception("No config file found.");
		}
		$this->config = $config;

		$this->dibi = new \DibiConnection(array(
			'driver' => 'sqlite3',
			'database' => __DIR__ . '/../../tmp/db.sdb'
		));
		$this->dibi->query('CREATE TABLE IF NOT EXISTS posts (id TEXT)');

		$this->crawlers['facebook'] = new FacebookGroups($this->dibi, $config['facebook']);
		$this->crawlers['bezrealitky'] = new Bezrealitky($this->dibi, $config['bezrealitky']);
		$this->crawlers['bazos'] = new Bazos($this->dibi, $config['bazos']);
		$this->crawlers['sreality'] = new Sreality($this->dibi, $config['sreality']);
		$this->crawlers['idnes'] = new Idnes($this->dibi, $config['idnes']);
		$this->crawlers['ismuni'] = new IsMuni($this->dibi, $config['ismuni']);
	}

	public function run()
	{
		$this->init();

		$seen = array();
		$notification = '';
		foreach ($this->crawlers as $code => $crawler) {
			foreach ($crawler->getNewPosts() as $post) {
				$notification .= sprintf('<h3>[%s] <a href="%s">%s</a></h3><br>%s<br><br>', $code, $post['url'], $post['title'], $post['message']);
				$seen[] = $post['url'];
			}
		}

		if (!empty($seen)) {
			$mailer = new Mail\SendmailMailer();
			$mail = new Mail\Message();
			foreach($this->config['app']['email_to'] as $email) {
				$mail->addTo($email);
			}
			$mail->setFrom($this->config['app']['email_from'])
				->setSubject(sprintf('%d new rent offer%s', count($seen), ((count($seen) > 1) ? 's' : '')))
				->setHtmlBody($notification);
			$mailer->send($mail);
		}

		foreach($seen as $url) {
			$this->dibi->query('INSERT INTO posts', array('id' => $url));
		}
	}

}
