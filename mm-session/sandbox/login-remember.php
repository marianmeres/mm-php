<?php
require_once __DIR__ . "/_top.php";

use MM\Session\Session;
use MM\Util\DbUtilPdo;

$start = microtime(true);
$dbu = new DbUtilPdo(new \PDO("sqlite:" . __DIR__ . "/_tmp/_session.sqlite"));
$dbu->activateQueryLog();

Session::setSaveHandler(new MM\Session\SaveHandler\DbTable(array(
    'dbu' => $dbu,
)));

// nastavime optiony ak treba
ini_set("session.name", "rambo");
ini_set("session.gc_probability", 1);
ini_set("session.gc_divisor", 1);

// a normalne pracujeme via staticky Session wrap
Session::start();
$session = Session::getNamespace('foo');

?>
<html>
<head><title>test</title></head>
<body>
<?php
    @$session->counter++;
    printf("<pre>%s</pre>", print_r($_SESSION, true));

    $redir = false;
    if (!empty($_REQUEST['login'])) {
        $session->loggedIn = 1;
        if (!empty($_REQUEST['remember_me'])) {
            Session::rememberMe(60*60);
        }
        $redir = true;
    } else if (!empty($_REQUEST['logout'])) {
        $session->loggedIn = 0;
        Session::forgetMe();
        $redir = true;
    }
    if ($redir) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

?>
<form action='' method='post' style='display: block;'>
<?php
if (!empty($session->loggedIn)) {
    echo "<input type='submit' name='logout' value='Logout'/>";
} else {
    echo "<label><input type='checkbox' name='remember_me' value='1'>
        Remember me</label> ";
    echo "<input type='submit' name='login' value='Login'/>";
}

?>
</form>
</body>
</html>