<?php
declare(strict_types=1);

namespace MM\Router;

use MM\Util\Str;

class Route {
	const SPLITTER = '/';

	protected string $_route;

	protected array $_parsed;

	protected int $_parsedCount;

	public function __construct(string $route) {
		$this->_route = $route;
		$this->_parsed = static::_parse($this->_route);
		$this->_parsedCount = count($this->_parsed);
	}

	public static function factory(string $route): Route {
		return new static($route);
	}

	protected static function _sanitizeAndSplit(string $str): array {
		$s = static::SPLITTER;
		$str = trim($str);

		// splitter trim left and right
		$out = preg_replace('/^(' . preg_quote($s, '/') . ')+/', '', $str);
		$out = preg_replace('/(' . preg_quote($s, '/') . ")+$/", '', $str);

		// filter empty + reindex
		return array_values(
			array_filter(explode('/', $out), function ($segment) {
				return $segment != '';
			}),
		);
	}

	protected static function _parse($route): array {
		$segments = static::_sanitizeAndSplit($route);
		$out = [];

		$wasSpread = false;
		foreach ($segments as $segment) {
			$name = null;

			$isOptional = Str::endsWith($segment, '?');
			if ($isOptional) {
				$segment = substr($segment, 0, -1);
			}

			$isSpread = Str::startsWith($segment, '[...');
			if ($isSpread) {
				// these two asserts are for sanity... otherwise the parsing logic would need
				// to be much more complicated while still lacking reasonable use case
				if ($isOptional) {
					throw new \Error("Spread segment must not be marked as optional");
				}
				if ($wasSpread) {
					throw new \Error("Multiple spread segments are invalid");
				}
				$wasSpread = true;
				$segment = '[' . substr($segment, 4);
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
				'isSpread' => $isSpread,
			];
		}

		return $out;
	}

	public function parse(string $url, bool $allowQueryParams = true): ?array {
		$matched = [];

		$qPos = strpos($url, '?');
		if ($allowQueryParams && $qPos != false) {
			$_backup = $url;
			$url = substr($_backup, 0, $qPos);
			parse_str(substr($_backup, $qPos + 1), $matched);
		}

		$segments = static::_sanitizeAndSplit($url);

		// SPREAD PARAMS DANCING BLOCK - if there are "spread" definitions we need to adjust input
		// that is "group" (join) segments that were initially splitted
		$hasSpread = !!count(array_filter($this->_parsed, fn ($v) => !!$v['isSpread']));
		if ($hasSpread) {
			$newSegments = [];
			foreach ($this->_parsed as $i => $p) {
				$inSpread = $p['isSpread'];
				if ($inSpread) {
					// there are defined segments after the "spread" definition
					if (array_key_exists($i + 1, $this->_parsed)) {
						$newSegments[] = join(
							self::SPLITTER,
							array_slice($segments, 0, $this->_parsedCount - $i)
						);
						$segments = array_slice($segments, $this->_parsedCount - $i);
					}
					// there are no more defined segments
					else {
						$newSegments[] = join(self::SPLITTER, $segments);
						break;
					}
				} else {
					$newSegments = array_merge($newSegments, array_slice($segments, 0, 1));
					$segments = array_slice($segments, 1);
				}
			}
			$segments = $newSegments;
		}

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

	public function dump() {
		return [
			'route' => $this->_route,
			'parsed' => $this->_parsed,
		];
	}
}
