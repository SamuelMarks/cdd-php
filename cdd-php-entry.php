<?php

$argv = $_SERVER["argv"];
$argv = array_slice($argv, 1);
$argc = count($argv);
$isCli = true;
$GLOBALS["argv"] = $argv;
$GLOBALS["argc"] = $argc;
$_SERVER["argv"] = $argv;
$_SERVER["argc"] = $argc;
require "bin_cdd_php.php";
