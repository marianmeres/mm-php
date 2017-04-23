<?php
spl_autoload_register(function ($class) {
    $class = ltrim($class, '\\');
    return include str_replace(
        array('\\', '_'), DIRECTORY_SEPARATOR, $class
    ) . '.php';
}, true, true);


// reset include path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(__DIR__ . "/../library"), // this package
    realpath(__DIR__ . "/../../mm-util/library"), // mm-util
    realpath(__DIR__ . "/../../mm-controller/library"), // mm-model
)));

if (!function_exists('prx')) {
    function prx($x) {
        if (!(bool)$x) {
            var_dump($x);
        } else {
            print_r($x);
        }
        exit;
    }
}

//
$sqlite = __DIR__ . "/_tmp/_session.sqlite";
if (!file_exists($sqlite)) {
    $pdo = new \PDO("sqlite:$sqlite");
    $pdo->exec(MM\Session\SaveHandler\DbTable::getDefaultSql());
}