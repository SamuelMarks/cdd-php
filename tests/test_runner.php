<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . '/framework/Runner.php';

$srcDir = realpath(__DIR__ . '/../src');
$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcDir));
foreach ($files as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
        require_once $file->getPathname();
    }
}

\Cdd\Tests\Framework\Runner::run(__DIR__);
