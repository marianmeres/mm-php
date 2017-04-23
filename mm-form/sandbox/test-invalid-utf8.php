<?php

$invalid = "\xf0\x28\x8c\x28";

$url = "http://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF'])
     . "/form-example.php?last=$invalid";

$out = file_get_contents($url);

// dirty hack
echo str_replace('action=""', 'action="form-example.php"', $out);
