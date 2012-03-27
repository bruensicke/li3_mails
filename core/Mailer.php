<?php

namespace li3_mailer\core;

use lithium\util\Set;
use lithium\util\String;

class Mailer extends \lithium\core\Adaptable {

	/**
	 * Stores configurations for various authentication adapters.
	 *
	 * @var object `Collection` of authentication configurations.
	 */
	protected static $_configurations = array();

	/**
	 * Stores event-types and their settings.
	 *
	 * @var array
	 */
	protected static $_types = array();

	/**
	 * View Object to be re-used across mails
	 *
	 * @see li3_mailer\core\Mailer::render()
	 * @var object Instance of View object
	 */
	protected static $_view;

	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.mailer';

	/**
	 * Dynamic class dependencies.
	 *
	 * @var array Associative array of class names & their namespaces.
	 */
	protected static $_classes = array(
		'view' => 'lithium\template\View',
	);

	/**
	 * Called when an adapter configuration is first accessed, this method sets the default
	 * configuration for session handling. While each configuration can use its own session class
	 * and options, this method initializes them to the default dependencies written into the class.
	 * For the session key name, the default value is set to the name of the configuration.
	 *
	 * @param string $name The name of the adapter configuration being accessed.
	 * @param array $config The user-specified configuration.
	 * @return array Returns an array that merges the user-specified configuration with the
	 *         generated default values.
	 */
	protected static function _initConfig($name, $config) {
		$defaults = array('adapter' => 'Mail');
		$config = parent::_initConfig($name, $config) + $defaults;
		return $config;
	}

	/**
	 * Acts as a proxy for the `track()` method, allowing Mails to be sent
	 * as method-names, i.e.:
	 * {{{
	 * Mailer::registed(compact('data'));
	 * // This is equivalent to Mailer::send('registered', compact('data'))
	 * }}}
	 *
	 * @see li3_mailer\core\Mailer::send()
	 * @param string $type The name of the method called on the `Mailer` class. This should map
	 *               to a mail type.
	 * @param array $params An array of parameters passed in the method.
	 * @return boolean Returns `true` or `false`, depending on the success of the `send()` method.
	 */
	public static function __callStatic($type, $params) {
		$params += array(array(), array());
		return static::send($type, $params[0], $params[1]);
	}

	/**
	 * Sends a Mail in an application, according to given $type.
	 *
	 * It fetches the correct template, parses it with given data and
	 * sends it, via the configured mailer.
	 *
	 * @param string $type what type of email to send
	 * @param string $data additional data to be used within email-template
	 * @param array $options additional options, incl. overwriting configured template
	 *        params, like overwriting the 'to', 'from', 'subject' etc.
	 * @return boolean Returns `true` or `false`, depending on the success of the `send()` method.
	 * @filter
	 */
	public static function send($type, array $data = array(), array $options = array()) {
		$defaults = array('name' => null);
		$options += $defaults;


		$content = Mailer::render($type, $data, $options);

		// if ($name = $options['name']) {
		// 	$methods = array($name => static::adapter($name)->send($type, $data, $options));
		// } else {
		// 	$methods = static::_configsByType($type, $data, $options);
		// }

		// foreach ($methods as $name => $method) {
		// 	$params = compact('type', 'data', 'options');
		// 	$config = static::_config($name);
		// 	$result &= static::_filter(__FUNCTION__, $params, $method, $config['filters']);
		// }
		// return $methods ? $result : false;
	}

	/**
	 * Configure Types as mails, that can be send.
	 *
	 * @see lithium\util\String::insert()
	 * @param string $types an array of types to be able to send
	 * @param array $options additional options, e.g.
	 *              - `'merge'` boolean: whether to merge given events with
	 *                existing ones, or not, defaults to true.
	 *              - `'replace'` boolean: whether to replace given events with
	 *                existing ones, or not, defaults to false.
	 * @return array all valid events, that are present afterwards
	 */
	public static function types(array $types, array $options = array()) {
		$defaults = array('merge' => true, 'replace' => false);
		$options += $defaults;
		if (!$options['replace'] || $options['merge']) {
			$types = array_merge(static::$_types, $types);
		}
		return static::$_types = $types;
	}

	public static function render($type, array $data = array(), array $options = array()) {
		$view = static::_view();
		$defaults = array(
			'layout' => 'default',
			'type' => 'html',
			'template' => $type,
		);
		$options += $defaults;
		$page = $view->render('template', array('content' => $data), $options);
		
		#debug($page);exit;
	}

	public static function _view(array $config = array()) {
		$defaults = array(
			'loader' => 'Simple',
			'renderer' => 'Simple',
			'paths' => array(
				'template' => '{:library}/mails/{:template}.{:type}.php',
				'layout'   => '{:library}/mails/layouts/{:layout}.{:type}.php',
			),
		);
		$config += $defaults;
		if (!empty(static::$_view)) {
			return static::$_view;
		}
		$view = static::$_classes['view'];
		static::$_view = new $view($config);
		return static::$_view;
	}

	/**
	 * Gets the names of the adapter configurations that respond to a specific type. The list
	 * of adapter configurations returned will be used to write Activity with the given type.
	 *
	 * @param string $type The Type of message to be written.
	 * @param string $data Array with additional data about that Activity.
	 * @param array $options Adapter-specific options.
	 * @return array Returns an array of names of configurations which are set up to respond to the
	 *         message types specified in `$types`, or configured to respond to _all_ activities.
	 */
	protected static function _configsByType($type, $data, array $options = array()) {
		$configs = array();
		$key = 'events';
		foreach (array_keys(static::$_configurations) as $name) {
			$config = static::config($name);
			$nameMatch = ($config[$key] === true || $config[$key] === $type);
			$arrayMatch = (is_array($config[$key]) && 
			(in_array($type, $config[$key]) || array_key_exists($type, $config[$key])));

			if ($nameMatch || $arrayMatch) {
				$method = static::adapter($name)->track($type, $data, $options);
				$method ? $configs[$name] = $method : null;
			}
		}
		return $configs;
	}
}

