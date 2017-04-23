<?php
require_once __DIR__ . "/_top.php";

use MM\Util\DbUtilPdo;

/**
 * USE CASE 1 - vlastny handler, inak uplny old school
 */

$start = microtime(true);

$handler = new MM\Session\SaveHandler\FlatFile(array(
    'dir' => __DIR__ . "/_tmp",
    'set_save_handler' => true,
));

// nastavime optiony ak treba
ini_set("session.name", "pix1flat");

// a normalne pracujeme
session_start();

echo (int) @$_SESSION['counter']++;

printf("<br/>%.3F", microtime(true) - $start);