<?php
$outDir = dirname($argv[1] ?? 'build/wasm_bundle.json');
if (!is_dir($outDir)) mkdir($outDir, 0777, true);

$finalFile = $argv[1] ?? 'build/wasm_bundle.json';

$baseDir = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS));

$files = [];
foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    $rel = substr($path, strlen($baseDir) + 1);
    
    if (preg_match('/^(src|vendor|bin)\//', $rel) && strpos($rel, 'bin/cdd-php.') === false) {
        $files[$rel] = file_get_contents($path);
    }
}

file_put_contents($finalFile, json_encode($files));
echo "Built WASM bundle JSON to $finalFile\n";
