<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;

/**
 * Class FunctionLoader
 * @package MM\Util
 *
 * Simle static wrapper on top of "require" to load custom global functions
 * at run-time
 *
 * Intentionaly does not use require_once but stores its own flags (should be
 * more performant theoretically)
 */
class FunctionLoader
{
    protected static $_loaded = array();

    public static function load($name)
    {
        if (!isset(self::$_loaded[$name])) {
            require __DIR__ . "/_functions/$name.php";
            self::$_loaded[$name] = 1;
        }
    }
}
