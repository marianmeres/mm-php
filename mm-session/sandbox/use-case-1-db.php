<?php
require_once __DIR__ . "/_top.php";

use MM\Util\DbUtilPdo;

/**
 * USE CASE 1 - vlastny handler, inak uplny old school
 */

$start = microtime(true);

$handler = new MM\Session\SaveHandler\DbTable(array(
    'dbu' => new DbUtilPdo(new \PDO("sqlite:" . __DIR__ . "/_tmp/_session.sqlite")),
    'set_save_handler' => true,
));

// nastavime optiony ak treba
ini_set("session.name", "pix1db");

// a normalne pracujeme
session_start();

echo (int) @$_SESSION['counter']++;

session_regenerate_id();

printf("<br/>%.3F", microtime(true) - $start);
