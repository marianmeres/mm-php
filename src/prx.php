<?php

if (!function_exists('prx')) {
	function prx(/* args */)
	{
		$numargs = func_num_args();
		$args = func_get_args();
		for ($i = 0; $i < $numargs; $i++) {
			$x = $args[$i];
			if (!(bool) $x) {
				var_dump($x);
			} else {
				print_r($x);
			}
		}
		exit();
	}
}
