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
    $rel = str_replace('\\', '/', $rel); // Normalize for Windows
    
    if (preg_match('/^(src|vendor|bin)\//', $rel) && strpos($rel, 'bin/cdd-php.') === false) {
        $files[$rel] = file_get_contents($path);
    }
}

$files['-q'] = "<?php\n\$_SERVER['argv'] = array_slice(\$_SERVER['argv'], 1);\n\$_SERVER['argc']--;\nrequire __DIR__ . '/bin/cdd-php';\n";
$files['php.ini'] = "html_errors = 0\ndisplay_errors = stderr\nmemory_limit = 512M\n";

file_put_contents($finalFile, json_encode($files));
echo "Built WASM bundle JSON to $finalFile\n";
