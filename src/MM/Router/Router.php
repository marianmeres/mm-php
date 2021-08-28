<?php

namespace MM\Router;

use phpDocumentor\Reflection\Types\Callable_;

class Router {
	protected array $_routes = [];

	protected $_catchAll;

	protected array $_current = ['route' => null, 'params' => null, 'label' => null];

	protected array $_subscriptions = [];

	public function __construct(array $config = []) {
		foreach ($config as $route => $cb) {
			$this->on($route, $cb);
		}
	}

	public function reset(): Router {
		$this->_routes = [];
		return $this;
	}

	public function current(): array {
		return $this->_current;
	}

	public function on($routes, callable $cb, array $addons = []) {
		if (!is_array($routes)) {
			$routes = [$routes];
		}

		// prettier-ignore
		foreach ($routes as $route) {
			if ($route === '*') {
				$this->_catchAll = $cb;
			} else {
				$this->_routes[] = [
					new Route($route),
					$cb,
                    !array_key_exists('allowQueryParams', $addons) || !!$addons['allowQueryParams'],
					array_key_exists('label', $addons) ? $addons['label'] : null,
				];
			}
		}
	}

	public function exec(string $url, callable $fallbackFn = null) {
		foreach ($this->_routes as $conf) {
			[$route, $cb, $allowQueryParams, $label] = $conf;
			$params = $route->parse($url, (bool) $allowQueryParams);
			if ($params !== null) {
				$this->_publishCurrent($route->dump()['route'], $params, $label);
				if (is_callable($cb)) {
					return $cb($params);
				}
			}
		}

		if (is_callable($fallbackFn)) {
			$this->_publishCurrent(null, null, null);
			return $fallbackFn();
		}

		if (is_callable($this->_catchAll)) {
			$this->_publishCurrent('*', null, null);
			return call_user_func($this->_catchAll);
		}

		$this->_publishCurrent(null, null, null);
		return false;
	}

	public function _publishCurrent($route, $params, $label) {
		$this->_current = ['route' => $route, 'params' => $params, 'label' => $label];
		foreach ($this->_subscriptions as $fn) {
			$fn($this->_current);
		}
	}

	public function subscribe(callable $fn): callable {
		$this->_subscriptions[] = $fn;
		$fn($this->current());

		// return unsubscribe fn
		return function () use ($fn) {
			if (($key = array_search($fn, $this->_subscriptions)) !== false) {
				unset($this->_subscriptions[$key]);
			}
		};
	}
}
