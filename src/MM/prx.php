<?php

if (!function_exists('prx')) {
	function prx(/* args */)
	{
		$args = func_get_args();
		foreach ($args as $x) {
			if (is_bool($x) || is_null($x)) {
				// ob_start();
				var_dump($x);
				// echo trim(ob_get_clean());
			} else {
				print_r($x);
			}
		}
		exit();
	}
}
