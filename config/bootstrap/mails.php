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
	),
	'postmark' => array(
		'adapter' => 'Postmark',
		'token' => 'foo',
		'templates' => array(
			'reset',
			'registered',
			'reminder',
			'password_reset'
		)
	),
));

Mailer::templates(array(
	'users/registered' => array(
		'from' => 'team@example.com',
		'subject' => 'Welcome {:name}',
		'template' => '{:name}',
		// 'template' => '{:locale}/registered',
		'layout' => 'default',
	),
	// 'users/reset' => array(
	// 	'from' => 'team@example.com',
	// 	'subject' => 'New pw for {:name}',
	// 	'template' => '{:name}',
	// 	// 'template' => '{:locale}/registered',
	// 	'layout' => 'default',
	// ),
));
