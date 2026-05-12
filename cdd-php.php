<?php
$_SERVER['argv'] = array_slice($_SERVER['argv'], 1);
$_SERVER['argc']--;
$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];
require __DIR__ . '/bin/cdd-php';
