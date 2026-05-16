<?php

array_shift($_SERVER["argv"]);
$_SERVER["argc"]--;
$argv = $_SERVER["argv"];
$argc = $_SERVER["argc"];
require __DIR__ . "/bin/cdd-php";
