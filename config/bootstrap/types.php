<?php

use li3_mailer\core\Mailer;

/**
 * Types should be configured, to use a certain set
 * of data for outgoing mails, like subject and from what
 * email-address to send.
 *
 * @see li3_mailer\core\Mailer::types()
 * @see li3_mailer\core\Mailer
 * @see lithium\core\Adaptable
 */
Mailer::types(array(
	'registered',
	'reminder',
	'password_reset'
	'saved' => '{:name} [{:id}] {:type}.',
));

