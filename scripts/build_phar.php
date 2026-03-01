<?php

$outDir = dirname($argv[1] ?? 'build/cdd-php.phar');
if (!is_dir($outDir)) mkdir($outDir, 0777, true);

$pharFile = $argv[1] ?? 'build/cdd-php.phar';

if (file_exists($pharFile)) {
    unlink($pharFile);
}

$phar = new Phar($pharFile);

$phar->buildFromDirectory(dirname(__DIR__), '/^(src|vendor|bin)\/.*$/');
$phar->addFile('vendor/autoload.php', 'vendor/autoload.php');

// Create the CLI stub
$defaultStub = $phar->createDefaultStub('bin/cdd-php');
$stub = "#!/usr/bin/env php
" . $defaultStub;
$phar->setStub($stub);

echo "Built phar successfully.
";
