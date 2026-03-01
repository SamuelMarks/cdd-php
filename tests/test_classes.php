<?php
require_once __DIR__ . '/functions/ParseEmitTest.php';
$classes = get_declared_classes();
$testClass = end($classes);
echo "testClass=$testClass\n";
