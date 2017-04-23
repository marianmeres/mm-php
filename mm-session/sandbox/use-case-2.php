<?php
require_once __DIR__ . "/_top.php";

use MM\Session\Session;
use MM\Util\DbUtilPdo;

/**
 * USE CASE 2 - session wrap. RECOMENDED
 */

$start = microtime(true);

// setupneme si dbuitl aj s logovanim (aby sme videli co sa deje)
$dbu = new DbUtilPdo(new \PDO("sqlite:" . __DIR__ . "/_tmp/_session.sqlite"));
$dbu->activateQueryLog();

// zaregistrujeme handler
Session::setSaveHandler(new MM\Session\SaveHandler\DbTable(array(
    'dbu' => $dbu,
)));

// nastavime optiony ak treba
ini_set("session.name", "pix2wrap");

// len pre islustraciu, ze vsetko funguje tak ako ma, dame 100% gc
ini_set("session.gc_probability", 1);
ini_set("session.gc_divisor"    , 1);

// a normalne pracujeme via staticky Session wrap
Session::start();


// nizsie uz cokolvek

printf("Old id: %s<br/>", Session::getId());

$session = Session::getNamespace('some');
echo (int) @$session->counter++;

Session::regenerateId(true);

printf("<br/>New id: %s<br/>", Session::getId());
echo (int) $session->counter++;

// Session::rememberMe(60); // 1 minute

// // Session::forgetMe();

// Session::setCookie();

// // toto robim iba aby bolo vidno log - inak to nie je treba
// Session::writeClose();

// echo "<pre>";
// print_r($dbu->getQueryLog());

// print_r($dbu->fetchAll('*', '_session'));

// printf("<br/>%.3F", microtime(true) - $start);
