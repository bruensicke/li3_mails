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
	 * Stores templates aka event-types and their settings.
	 *
	 * @var array
	 */
	protected static $_templates = array();

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
		$defaults = array('adapter' => 'Mail', 'templates' => array());
		$config = parent::_initConfig($name, $config) + $defaults;
		return $config;
	}

	/**
	 * Acts as a proxy for the `send()` method, allowing Mails to be sent
	 * as method-names, i.e.:
	 * {{{
	 * Mailer::registered(compact('data'));
	 * // This is equivalent to Mailer::send('registered', compact('data'))
	 * }}}
	 *
	 * @see li3_mailer\core\Mailer::send()
	 * @param string $template The name of the method called on the `Mailer` class. This should map
	 *               to a mail template.
	 * @param array $params An array of parameters passed in the method.
	 * @return boolean Returns `true` or `false`, depending on the success of the `send()` method.
	 */
	public static function __callStatic($template, $params) {
		$params += array(array(), array());
		return static::send($template, $params[0], $params[1]);
	}

	/**
	 * Sends a Mail in an application, according to given $template.
	 *
	 * It fetches the correct template, parses it with given data and
	 * sends it, via the configured mailer. All default data from configuration
	 * is loaded in advance, so you can globally define from/to/cc and stuff.
	 *
	 * @param string $template what template of email to send
	 * @param string $data additional data to be used within email-template
	 * @param array $options additional options, incl. overwriting configured template
	 *        params, like overwriting the 'to', 'from', 'subject' etc.
	 * @return boolean Returns `true` or `false`, depending on the success of the `send()` method.
	 * @filter
	 */
	public static function send($template, array $data = array(), array $options = array()) {
		$defaults = array('name' => null);
		$options += $defaults;

		$options = static::_optionsByTemplate($template, $data, $options);

// debug($options);
// debug($data);
// 		$content = Mailer::render($template, $data, $options);
// debug($content);


		if ($name = $options['name']) {
			$methods = array($name => static::adapter($name)->send($type, $data, $options));
		} else {
			$methods = static::_configsByTemplate($template, $data, $options);
		}
// debug($methods);
		// foreach ($methods as $name => $method) {
		// 	$params = compact('type', 'data', 'options');
		// 	$config = static::_config($name);
		// 	$result &= static::_filter(__FUNCTION__, $params, $method, $config['filters']);
		// }
		// return $methods ? $result : false;
	}

	/**
	 * Configure templates as mails, that can be send.
	 *
	 * @see lithium\util\String::insert()
	 * @param string $templates an array of templates to be able to send
	 *               including their default configuration
	 * @param array $options additional options, e.g.
	 *              - `'merge'` boolean: whether to merge given events with
	 *                existing ones, or not, defaults to true.
	 *              - `'replace'` boolean: whether to replace given events with
	 *                existing ones, or not, defaults to false.
	 * @return array all valid events, that are present afterwards
	 */
	public static function templates(array $templates, array $options = array()) {
		$defaults = array('merge' => true, 'replace' => false);
		$options += $defaults;
		if (!$options['replace'] || $options['merge']) {
			$templates = array_merge(static::$_templates, $templates);
		}
		return static::$_templates = $templates;
	}

	/**
	 * renders the given template with given data
	 *
	 * @param string $template 
	 * @param array $data 
	 * @param array $options 
	 * @return void
	 */
	public static function render($template, array $data = array(), array $options = array()) {
		$view = static::_view();
		$defaults = array(
			'layout' => 'default',
			'type' => 'html',
			'template' => $template,
			'process' => 'all', // can also be 'template'
		);
		$options += $defaults;
		return $view->render($options['process'], $data, $options);
	}

	public static function _view(array $config = array()) {
		$defaults = array(
			// 'loader' => 'File',
			// 'renderer' => 'File',
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
		return static::$_view = new $view($config);
	}

	protected static function _optionsByTemplate($template, $data, array $options = array()) {
		$_templates = static::$_templates;
		if (array_key_exists($template, static::$_templates)) {
			$options = Set::merge(static::$_templates[$template], $options);
		}
		return $options;
	}

	/**
	 * Gets the names of the adapter configurations that act on a specific template. The list
	 * of adapter configurations returned will be used to send Mails with the given configuration.
	 *
	 * @param string $template The Type of message to be send.
	 * @param string $data Array with additional data about that Mail.
	 * @param array $options Adapter-specific options.
	 * @return array Returns an array of names of configurations which are set up to respond to the
	 *         message types specified in `$types`, or configured to respond to _all_ activities.
	 */
	protected static function _configsByTemplate($template, $data, array $options = array()) {
		$configs = array();
		$key = 'templates';
		foreach (array_keys(static::$_configurations) as $name) {
			$config = static::config($name);
			debug($config);
			$nameMatch = ($config[$key] === true || $config[$key] === $template);
			$arrayMatch = (is_array($config[$key]) && 
			(in_array($template, $config[$key]) || array_key_exists($template, $config[$key])));

			if ($nameMatch || $arrayMatch) {
				$method = static::adapter($name)->send($template, $data, $options);
				$method ? $configs[$name] = $method : null;
			}
		}
		return $configs;
	}
}

