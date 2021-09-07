<?php declare(strict_types=1);

namespace MM\Util;

class Url {
	/**
	 * Trosku normalizuje chovanie parse_url
	 */
	public static function parse($url, $component = null): array|string {
		$parsed = array_merge(
			[
				'scheme' => '',
				'user' => '',
				'pass' => '',
				'host' => '',
				'port' => '',
				'path' => '',
				'query' => '',
				'fragment' => '',
			],
			(array) parse_url($url),
		);

		if ($parsed['query']) {
			// note: druhy arg je tu referencia na output... cize prepiseme query
			parse_str($parsed['query'], $parsed['query']);
		}

		if (null == $component) {
			return $parsed;
		}

		if (isset($parsed[$component])) {
			return $parsed[$component];
		}

		throw new \InvalidArgumentException("Invalid component '$component'");
	}

	/**
	 * Vysklada url podla casti. Nieco ako opozit k parse_url.
	 * Stoji za poznamku, ze neriesi ziadne pokrocile validovanie...
	 */
	public static function build(array $urlParts): string {
		$scheme = $user = $pass = $host = $hostname = $port = $path = $query = $fragment =
			'';

		if (!empty($urlParts['query']) && is_array($urlParts['query'])) {
			$urlParts['query'] = http_build_query($urlParts['query']);
		}

		$urlParts = array_merge(
			[
				'scheme' => '',
				'user' => '',
				'pass' => '',
				'host' => 'hostname',
				'port' => '',
				'path' => '',
				'query' => '',
				'fragment' => '',
			],
			$urlParts,
		);
		extract($urlParts);

		// 'http://username:password@hostname/path?arg=value#anchor';
		$url = '//';

		if ('' != $scheme) {
			$url = "$scheme:$url";
		}

		if ('' != $user) {
			$url .= $user;
			if ('' != $pass) {
				$url .= ":$pass";
			}
			$url .= '@';
		}

		$url .= rtrim($host, '/');

		if ('' != $port) {
			$url .= ":$port";
		}

		$url .= '/' . ltrim($path, '/');

		if ('' != $query) {
			$url .= "?$query";
		}

		if ('' != $fragment) {
			$url .= "#$fragment";
		}

		return $url;
	}

	public static function withQueryVars(string $url, array $vars): string {
		$parsed = Url::parse($url);

		// merge parsed with arg, filter nulls
		$parsed['query'] = array_filter(
			array_merge($parsed['query'], $vars), fn ($v) => $v !== null
		);
		ksort($parsed['query']);

		return Url::build($parsed);
	}

	/**
	 * HTTP_HOST versus SERVER_NAME?
	 * http://stackoverflow.com/questions/2297403/http-host-vs-server-name
	 * http://stackoverflow.com/questions/1459739/php-serverhttp-host-vs-serverserver-name-am-i-understanding-the-ma
	 */
	public static function serverUrl(array|null $server = null, string $hostKey = 'SERVER_NAME'): string {
		if (!$server) {
			$server = $_SERVER;
		}

		$port = !empty($server['SERVER_PORT']) ? $server['SERVER_PORT'] : 80;

		$url =
			'http' .
			(!empty($server['HTTPS']) ? 's' : '') .
			'://' .
			(!empty($server[$hostKey]) ? $server[$hostKey] : 'unknown-host') .
			(preg_match('/^(80|443)$/', $port) ? '' : ":$port") .
			(!empty($server['REQUEST_URI']) ? $server['REQUEST_URI'] : '/');

		return $url;
	}
}
