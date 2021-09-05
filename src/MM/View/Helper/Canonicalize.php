<?php declare(strict_types=1);

namespace MM\View\Helper;
use MM\Util\Url;
use MM\View\Exception;
use MM\View\Helper;

class Canonicalize extends Helper {
	public function __invoke($url): string {
		return self::url($url);
	}

	public static function url($url): string {
		$parts = Url::parse($url);
		if (!empty($parts['path'])) {
			$parts['path'] = self::path($parts['path']);
		}
		return Url::build($parts);
	}

	public static function path($path): string {
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
		if ('./' === $first2) {
			$out = "./$out";
		} elseif (str_starts_with($first2, '/')) {
			$out = "/$out";
		}

		// add trailing slash if there was one
		if ($last === '/') {
			$out = rtrim($out, '/') . '/';
		}

		return $out;
	}
}
