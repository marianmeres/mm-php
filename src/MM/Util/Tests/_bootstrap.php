<?php
require_once __DIR__ . '/../../../../vendor/autoload.php';

if (!function_exists('getEnvFromPhpUnitXmlConfig')) {
	function getEnvFromPhpUnitXmlConfig($name, $default = null) {
		$opts = getopt('c:');
		// prx(__DIR__ . "/$opts[c]");
		if (!empty($opts['c'])) {
			// $phpunit = simplexml_load_file(__DIR__ . "/$opts[c]");
			$phpunit = simplexml_load_file("$opts[c]");
			foreach ($phpunit->php[0]->env as $_env) {
				if ($_env['name'] && $name == current($_env['name'])) {
					if ($_env['value']) {
						return current($_env['value']);
					}
					return $default;
				}
			}
		}
		return $default;
	}
}

// global config if any
$config = [];
foreach (['_config', '_config.dist'] as $k) {
	if (file_exists(__DIR__ . "/$k.php")) {
		$config = require __DIR__ . "/$k.php";
		break;
	}
}

if (!defined('MM_UTIL_DB_VENDOR')) {
	define(
		'MM_UTIL_DB_VENDOR',
		getEnvFromPhpUnitXmlConfig('MM_UTIL_DB_VENDOR', 'sqlite'),
	);
}

// tu si to konverneme na json, lebo potrebujeme primitivny typ do konstatny
if (!defined('MM_UTIL_PDO_JSON_CONFIG')) {
	define('MM_UTIL_PDO_JSON_CONFIG', json_encode($config[MM_UTIL_DB_VENDOR]));
}
