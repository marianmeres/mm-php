<?php
spl_autoload_register(function ($class) {
    $class = ltrim($class, '\\');
    return include str_replace(
        array('\\', '_'), DIRECTORY_SEPARATOR, $class
    ) . '.php';
}, true, true);

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(__DIR__ . "/../library"),
    realpath(__DIR__ . "/../../mm-util/library"),
)));

if (!function_exists('prx')) {
    function prx($x) {
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
