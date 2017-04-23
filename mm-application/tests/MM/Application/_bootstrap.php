<?php
/*******************************************************************************
 * util/helpers
 ******************************************************************************/

if (!function_exists('prx')) {
    function prx(/* args */) {
        $numargs = func_num_args();
        $args = func_get_args();
        for ($i = 0; $i < $numargs; $i++) {
            $x = $args[$i];
            if (!(bool)$x) {
                var_dump($x);
            } else {
                print_r($x);
            }
        }
        exit;
    }
}

if (!function_exists('getEnvFromPhpUnitXmlConfig')) {
    function getEnvFromPhpUnitXmlConfig($name, $default = null) {
        $opts = getopt('c:');
        if (!empty($opts['c'])) {
            $phpunit = simplexml_load_file(__DIR__ . "/$opts[c]");
            foreach($phpunit->php[0]->env as $_env) {
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


/*******************************************************************************
 * common config
 ******************************************************************************/

// PSR-0 compatible autoloader
spl_autoload_register(function ($class) {
    $class = ltrim($class, '\\');
    $classPath = str_replace(array('\\', '_'), '/', $class) . '.php';
    if ($file = stream_resolve_include_path($classPath)) {
        return require $file;
    }
});


/*******************************************************************************
 * custom config below
 ******************************************************************************/

// reset include path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(__DIR__ . "/../../../library"), // this package
    realpath(__DIR__ . "/../../../tests"), // this package test dir
    realpath(__DIR__ . "/../../../../mm-util/library"), // mm-util
    realpath(__DIR__ . "/../../../../mm-controller/library"), // mm-util
)));


// global config if any
$config = array();
foreach (array('_config', '_config.dist') as $k) {
    if (file_exists(__DIR__ . "/$k.php")) {
        $config = require __DIR__ . "/$k.php";
        break;
    }
}

