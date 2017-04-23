<?php
// toto sa loadne az po tom, co skusi pozriet na "custom"
return array(
    'sqlite' => array(
        'driver'   => 'sqlite',
        'database' => ':memory:',
    ),
    'mysql' => array(
        'driver'   => 'mysql',
        'hostname' => '127.0.0.1',
        'database' => 'phpunit_tests',
        'username' => 'root',
        'password' => '',
    ),
    'pgsql' => array(
        'driver'   => 'pgsql',
        'hostname' => '127.0.0.1',
        'database' => 'phpunit_tests',
        'username' => 'root',
        'password' => '',
    ),
);