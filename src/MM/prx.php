<?php

if (!function_exists('pr')) {
	function pr(/* args */) {
		$args = func_get_args();
		foreach ($args as $x) {
			if (is_bool($x) || is_null($x)) {
				var_dump($x);
			} else {
				print_r($x);
			}
		}
	}
}

if (!function_exists('prx')) {
	function prx(/* args */) {
		call_user_func_array('pr', func_get_args());
		exit();
	}
}

if (!function_exists('prxh')) {
	function prxh(/* args */) {
		header('Content-type: text/plain');
		echo "\n";
		call_user_func_array('prx', func_get_args());
	}
}
