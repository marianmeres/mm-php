<?php
/**
 * Inspired by ZF1. Simplified.
 * @author  Marian Meres
 */
namespace MM\Controller;

use MM\Controller\Exception;
use MM\Controller\Helper;

/**
 * Class AbstractController
 * @package MM\Controller
 */
abstract class AbstractController
{
	/**
	 * Internal container for _GET, _POST, _SERVER and custom data.
	 * Usage: $this->params()->key
	 *
	 * @var Params
	 */
	protected $_params;

	/**
	 * @var Response
	 */
	protected $_response;

	/**
	 * Last thrown exception
	 * @var Exception
	 */
	protected $_exception;

	/**
	 * Internal test helper flag (mainly to rethrow exceptions)
	 * @var bool
	 */
	public $testMode = false;

	/**
	 * Instantiated helper instances
	 * @var array
	 */
	protected $_helpers = [];

	/**
	 * @var bool
	 */
	protected $_obEnabled = true;

	/**
	 * @var \ArrayAccess
	 */
	protected $_dic;

	/**
	 *
	 */
	public function __construct(array $params = [], array $options = null)
	{
		// params parameter je len shorcut z optionov
		if (!empty($params)) {
			$options['params'] = $params;
		}

		if ($options) {
			$this->setOptions($options);
		}

		$this->_init();
	}

	/**
	 *
	 */
	public static function factory(
		$params = [],
		array $options = null
	): AbstractController {
		return new static($params, $options);
	}

	/**
	 *
	 */
	public function setParams($params): AbstractController
	{
		if (is_array($params)) {
			$params = new Params($params);
		}

		if (null !== $params && !$params instanceof Params) {
			throw new Exception('Invalid params instance');
		}

		$this->_params = $params;
		return $this;
	}

	/**
	 * Init hook
	 */
	protected function _init()
	{
	}

	/**
	 *
	 */
	public function setDic(\ArrayAccess $dic): AbstractController
	{
		$this->_dic = $dic;
		return $this;
	}

	/**
	 *
	 */
	public function getDic(): \ArrayAccess
	{
		return $this->_dic;
	}

	/**
	 *
	 */
	public function setObEnabled(bool $flag = true): AbstractController
	{
		$this->_obEnabled = (bool) $flag;
		return $this;
	}

	/**
	 *
	 */
	public function params(): Params
	{
		if (!$this->_params) {
			$this->_params = new Params();
		}
		return $this->_params;
	}

	/**
	 *
	 */
	public function response(): Response
	{
		if (null == $this->_response) {
			$this->_response = new Response();
		}
		return $this->_response;
	}

	/**
	 *
	 */
	public function setResponse(Response $response): AbstractController
	{
		$this->_response = $response;
		return $this;
	}

	/**
	 * Sets options which have normalized setter. Otherwise throws.
	 * @throws Exception
	 */
	public function setOptions(array $options): AbstractController
	{
		foreach ($options as $_key => $value) {
			$key = str_replace('_', ' ', strtolower(trim($_key))); // under_scored to CamelCase
			$key = str_replace(' ', '', ucwords($key));
			$method = "set$key";
			if (!method_exists($this, $method)) {
				throw new Exception("Unknown controller option '$_key'");
			}
			$this->$method($value);
		}
		return $this;
	}

	/**
	 * Pre action execution hook. May modify action if needed (e.g. on acl check)
	 */
	protected function _preDispatch()
	{
	}

	/**
	 * Post action execution hook
	 */
	protected function _postDispatch()
	{
	}

	/**
	 *
	 */
	public function setException(\Exception $e = null): AbstractController
	{
		$this->_exception = $e;
		return $this;
	}

	/**
	 *
	 */
	public function getException(): \Exception
	{
		return $this->_exception;
	}

	/**
	 *
	 */
	public function getActionMethodName($action = null): string
	{
		if (empty($action)) {
			$action = $this->server()->getRequestMethod();
		}

		return self::_normalizeActionName($action) . 'Action';
	}

	/**
	 * @throws Exception
	 * @throws Exception\PageNotFound
	 */
	public function __call(string $name, array $arguments)
	{
		// najskor skusime handler akcie
		if ('Action' == substr($name, -6)) {
			throw new Exception\PageNotFound("Missing action handler for '$name'", 404);
		}

		throw new Exception("Missing controller method '$name'");
	}

	/**
	 * Dispatches to action handler. Calls pre/post hooks. Catches action's
	 * exceptions if found - and forwards to errorAction.
	 */
	public function dispatch(string $action = null, bool $output = false): Response
	{
		// jednotlive kroky (vratane erroru) bufferujeme samostatne aby sme
		// mali segmenty pod kontrolou v kazdom z nich
		//
		// NOTE: nie je nevyhnutne aby akcie echovali... mozu surovo zapisovat
		// do responsu ak uznaju za vhodne

		try {
			// rethrow a skip cely dispatch ak nahodou uz mame co hodit...
			if ($this->_exception) {
				throw $this->_exception;
			}

			// output buffering citame umyselne nad kazdym volanim
			// pre/post/dispatch...

			// pre dispatch
			$ob = $this->_obEnabled;
			$ob && ob_start();
			$this->_preDispatch();
			$ob && $this->response()->setBody(ob_get_clean(), false);

			// dispatch
			$ob = $this->_obEnabled;
			$ob && ob_start();
			$method = $this->getActionMethodName($action);
			$this->$method();
			$ob && $this->response()->setBody(ob_get_clean(), false);

			// post dispatch
			$ob = $this->_obEnabled;
			$ob && ob_start();
			$this->_postDispatch();
			$ob && $this->response()->setBody(ob_get_clean(), false);
		} catch (\Exception $e) {
			// exceptiony chceme vypluvat na cistej luke... je legitimne tu byt
			// vnoreny viac krat, preto takto:
			while (ob_get_level()) {
				ob_end_clean();
			}

			// reset body
			$this->response()->setBody([], true);

			// save exception
			$this->setException($e);

			// error output ob obalujeme...
			$errBody = '';
			ob_start();
			$this->errorAction();
			// tu nevieme garantovat co stvaral s ob errorAction, preto:
			while (ob_get_level()) {
				$errBody .= ob_get_clean();
			}
			$this->response()->setBody($errBody, false);
		}

		if ($output) {
			return $this->response()->output();
		}

		return $this->response();
	}

	/**
	 *
	 */
	protected static function _normalizeActionName(string $action): string
	{
		// normalizes "aa.bb-cc_DD/eE" to "aaBbCcDdEe"
		return lcfirst(
			str_replace(
				' ',
				'',
				ucwords(
					str_replace(['.', '-', '/', '_'], ' ', ucfirst(strtolower($action)))
				)
			)
		);
	}

	/**
	 *  To be overwritten;
	 */
	public function getAction()
	{
		echo get_class($this) . ': It Works!';
	}

	/**
	 * Built-in naive error handler. To be overwritten (extended) at project level,
	 */
	public function errorAction()
	{
		$c = $this;
		$e = $c->getException();

		if ($e) {
			$code = $c->response()->isValidStatusCode($e->getCode())
				? $e->getCode()
				: 500;
			$c->response()->setStatusCode($code);

			if ($c->testMode) {
				throw $e; // rethrow
			}

			printf('<b>Oops...</b> %s', $e->getMessage());

			if (ini_get('display_errors')) {
				printf(
					'<hr/>%s: %s<pre>%s',
					get_class($e),
					$e->getCode(),
					$e->getTraceAsString()
				);
			}
		} else {
			echo "You've reached the error page.";
		}
	}

	/**
	 * Sugar
	 * @throws Exception
	 */
	public function redirect(string $url, bool $permanent = true): AbstractController
	{
		if ('' === "$url") {
			$url = '.';
		}
		$this->response()->setHeader('Location', $url);
		$this->response()->setStatusCode($permanent ? 301 : 302);
		return $this;
	}

	/**
	 * @throws Exception
	 */
	public function assertExpectedParams(array $keys): array
	{
		$out = [];
		// sanity checks
		foreach ($keys as $k) {
			$param = $this->params()->$k;
			//if (null === $param) { // toto pusti empty string... napr: x ak je ?x=&y=1
			if ('' === "$param") {
				throw new Exception("Missing expected non empty '$k' param");
			} else {
				$out[$k] = $param;
			}
		}
		return $out;
	}

	/**
	 *
	 */
	protected function _normalizeHelperName(string $name): string
	{
		return strtolower($name);
	}

	/**
	 * @throws Exception
	 */
	public function setHelper(
		string $name,
		$fqnOrInstance = null,
		bool $reset = false
	): AbstractController {
		$name = $this->_normalizeHelperName($name);

		//
		if ($reset) {
			unset($this->_helpers[$name]);
		}
		// if not reseting return if found
		elseif (isset($this->_helpers[$name])) {
			return $this;
		}

		// if null, return early nothing
		if (null === $fqnOrInstance) {
			return $this;
		}

		// string or instance
		if (is_string($fqnOrInstance) || $fqnOrInstance instanceof Helper) {
			$this->_helpers[$name] = $fqnOrInstance;
			return $this;
		}

		//
		throw new Exception(
			'Invalid controller helper. Expecting either fully qualified ' .
				'class name or actual Helper instance'
		);
	}

	/**
	 * @throws Exception
	 */
	public function setHelpers(array $nameToFqn): AbstractController
	{
		foreach ($nameToFqn as $name => $fqn) {
			$this->setHelper($name, $fqn);
		}
		return $this;
	}

	/**
	 * @throws Exception
	 */
	public function getHelper(string $name, $fallbackFqnOrInstance = null): Helper
	{
		$name = $this->_normalizeHelperName($name);

		//
		if (!isset($this->_helpers[$name])) {
			// use fallback if provided
			if ($fallbackFqnOrInstance) {
				$this->setHelper($name, $fallbackFqnOrInstance);
			} else {
				return null;
			}
		}

		// JIT, lazy, string to instance
		if (is_string($this->_helpers[$name])) {
			$helper = new $this->_helpers[$name]($this);
			// note1: use setter because it validates
			// note2: important $reset=true (3rd param)
			$this->setHelper($name, $helper, true);
		}

		//
		return $this->_helpers[$name];
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function hasHelper($name)
	{
		$name = $this->_normalizeHelperName($name);
		return isset($this->_helpers[$name]);
	}

	/**
	 * Built in helper
	 * @return \MM\Controller\Helper\Server
	 */
	public function server()
	{
		return $this->getHelper('server', '\MM\Controller\Helper\Server');
	}
}
