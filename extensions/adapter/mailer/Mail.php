<?php

namespace li3_mailer\extensions\adapter\mailer;

use li3_mailer\core\Mailer;

class Mail extends \lithium\core\Object {

	/**
	 * Class constructor.
	 *
	 * @param array $config Settings used to configure the adapter.
	 */
	public function __construct(array $config = array()) {
		$defaults = array();
		parent::__construct($config + $defaults);
	}

	/**
	 * Sends a Mail via mail()
	 *
	 * @param string $type what type of email to send
	 * @param string $data additional data to be used within email-template
	 * @param array $options additional options, incl. overwriting configured template
	 *        params, like overwriting the 'to', 'from', 'subject' etc.
	 * @return boolean Returns `true` or `false`, depending on the success of the `send()` method.
	 */
	public function send($type, array $data = array(), array $options = array()) {
		$config = $this->_config;
		$params = compact('type', 'data', 'options');
		return function($self, $params) use (&$config) {

debug($options);
debug($data);
		$content = Mailer::render($template, $data, $options);
debug($content);


			debug($params);
			// return mail();
		};
	}
}
