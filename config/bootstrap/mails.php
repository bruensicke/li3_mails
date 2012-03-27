<?php

use li3_mailer\core\Mailer;

/**
 * Default configuration uses mail() to send mails.
 *
 * @see li3_mailer\core\Mailer
 * @see lithium\core\Adaptable
 */
Mailer::config(array(
	'default' => array(
		'adapter' => 'Mail',
		'to' => 'bcc@example.com',
		'types' => true,
	),
	'postmark' => array(
		'adapter' => 'Postmark',
		'token' => 'foo',
		'types' => array(
			'registered',
			'reminder',
			'password_reset'
		)
	),
));

Mailer::types(array(
	'users/registered' => array(
		'from' => 'team@example.com',
		'subject' => 'Welcome {:name}',
		'template' => '{:name}',
		// 'template' => '{:locale}/registered',
		'layout' => 'default',
	),
));
