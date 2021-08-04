<?php
/**
 * @author Marian Meres
 */
namespace MM\View\Helper;
use MM\Util\Url;
use MM\View\Exception;
use MM\View\Helper;

/**
 * @package MM\View\Helper
 */
class Canonicalize extends Helper {
	/**
	 * @param $url
	 * @return string
	 */
	public function __invoke($url) {
		return self::url($url);
	}

	/**
	 * @param $url
	 * @return string
	 */
	public static function url($url) {
		$parts = Url::parse($url);
		if (!empty($parts['path'])) {
			$parts['path'] = self::path($parts['path']);
		}
		return Url::build($parts);
	}

	/**
	 * @param $path
	 * @return array|string
	 */
	public static function path($path) {
		$out = [];

		// normalize directory separator (use "/")
		$path = str_replace('\\', '/', $path);

		// save first 2 chars (we're looking for '/', './')
		$first2 = substr($path, 0, 2);

		$last = substr($path, -1);

		// explode and process not empty segments
		$parts = array_filter(explode('/', $path), 'strlen');
		foreach ($parts as $part) {
			if ('.' != $part) {
				// ignore .
				'..' == $part ? array_pop($out) : array_push($out, $part);
			}
		}

		$out = implode('/', $out);

		//
		if ('./' == $first2) {
			$out = "./$out";
		} elseif ('/' == substr($first2, 0, 1)) {
			$out = "/$out";
		}

		// add trailing slash if there was one
		if ($last == '/') {
			$out = rtrim($out, '/') . '/';
		}

		return $out;
	}
}
