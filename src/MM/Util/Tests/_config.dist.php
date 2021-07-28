<?php
// toto sa loadne az po tom, co skusi pozriet na "custom"
return [
	'sqlite' => [
		'driver' => 'sqlite',
		'database' => ':memory:',
	],
	'mysql' => [
		'driver' => 'mysql',
		'hostname' => '127.0.0.1',
		'database' => 'phpunit_tests',
		'username' => 'dbuser',
		'password' => 'dbpassword',
	],
	'pgsql' => [
		'driver' => 'pgsql',
		'hostname' => '127.0.0.1',
		'database' => 'phpunit_tests',
		'username' => 'dbuser',
		'password' => 'dbpassword',
	],
];
