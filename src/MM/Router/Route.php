<?php

namespace MM\Router;

use MM\Util\Str;

class Route
{
	const SPLITTER = '/';

	protected $_route;

	protected $_parsed;

	public function __construct(string $route)
	{
		$this->_route = $route;
		$this->_parsed = static::_parse($this->_route);
	}

	public static function factory(string $route): Route
	{
		return new static($route);
	}

	protected static function _sanitizeAndSplit(string $str): array
	{
		$s = static::SPLITTER;
		$str = trim($str);

		// splitter trim left and right
		$out = preg_replace('/^(' . preg_quote($s, '/') . ')+/', '', $str);
		$out = preg_replace('/(' . preg_quote($s, '/') . ")+$/", '', $str);

		// filter empty + reindex
		return array_values(
			array_filter(explode('/', $out), function ($segment) {
				return $segment != '';
			})
		);
	}

	protected static function _parse($route): array
	{
		$segments = static::_sanitizeAndSplit($route);
		$out = [];

		foreach ($segments as $segment) {
			$name = null;
			$isOptional = Str::endsWith($segment, '?');
			if ($isOptional) {
				$segment = substr($segment, 0, -1);
			}

			$test = '/^' . preg_quote($segment, '/') . '$/';

			// starting with at least one word char within brackets...
			if (preg_match('/^\[(\w.+)]$/', $segment, $m)) {
				$name = $m[1];
				$test = '/.+/';

				// id([0-9]+)
				if (preg_match('/^(\w.*)\((.+)\)$/', $m[1], $m2)) {
					$name = $m2[1];
					$test = '/^' . $m2[2] . '$/';
				}
			}

			$out[] = [
				'segment' => $segment,
				'name' => $name,
				'test' => $test,
				'isOptional' => $isOptional,
			];
		}

		return $out;
	}

	public function parse(string $url, bool $allowQueryParams = true)
	{
		$matched = [];

		$qPos = strpos($url, '?');
		if ($allowQueryParams && $qPos != false) {
			$_backup = $url;
			$url = substr($_backup, 0, $qPos);
			parse_str(substr($_backup, $qPos + 1), $matched);
		}

		$segments = static::_sanitizeAndSplit($url);

		// minimum required (not optional) segments length
		$reqLen = 0;
		foreach ($this->_parsed as $i => $p) {
			$next = array_key_exists($i + 1, $this->_parsed)
				? $this->_parsed[$i + 1]
				: false;
			if (!$p['isOptional'] || ($next && !$next['isOptional'])) {
				$reqLen++;
			}
		}

		// quick cheap check: if counts dont match = no match
		if (count($segments) < $reqLen) {
			return null;
		}
		foreach ($segments as $i => $s) {
			if (!array_key_exists($i, $this->_parsed)) {
				return null;
			}
			$p = $this->_parsed[$i];
			if (!preg_match($p['test'], $s)) {
				return null;
			}
			if ($p['name']) {
				$matched[urldecode($p['name'])] = urldecode($s);
			}
		}

		return $matched;
	}

	public function dump()
	{
		return [
			'route' => $this->_route,
			'parsed' => $this->_parsed,
		];
	}
}
