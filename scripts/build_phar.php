<?php

$outDir = dirname($argv[1] ?? 'build/cdd-php');
if (!is_dir($outDir)) mkdir($outDir, 0777, true);

$finalFile = $argv[1] ?? 'build/cdd-php';
$pharFile = $finalFile . '.phar';

if (file_exists($pharFile)) unlink($pharFile);
if (file_exists($finalFile)) unlink($finalFile);

$phar = new Phar($pharFile);

$baseDir = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS));

$files = [];
foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    $rel = substr($path, strlen($baseDir) + 1);
    
    if (preg_match('/^(src|vendor|bin)\//', $rel) && strpos($rel, 'bin/cdd-php.') === false) {
        $files[$rel] = $path;
    }
}

$phar->buildFromIterator(new ArrayIterator($files));
$phar->addFile($baseDir . '/vendor/autoload.php', 'vendor/autoload.php');

$stub = "#!/usr/bin/env php\n<?php\nPhar::mapPhar('cdd-php.phar');\nrequire 'phar://cdd-php.phar/bin/cdd-php';\n__HALT_COMPILER();";
$phar->setStub($stub);

rename($pharFile, $finalFile);
chmod($finalFile, 0755);
echo "Built phar successfully to $finalFile\n";
