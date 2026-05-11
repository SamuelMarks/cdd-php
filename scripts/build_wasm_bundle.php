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

$files['cdd-php'] = "<?php\n\$argv = \$_SERVER['argv'];\narray_unshift(\$argv, 'cdd-php');\n\$argc = count(\$argv);\n\$isCli = true;\n\$GLOBALS['argv'] = \$argv;\n\$GLOBALS['argc'] = \$argc;\n\$_SERVER['argv'] = \$argv;\n\$_SERVER['argc'] = \$argc;\nrequire __DIR__ . '/bin/cdd-php';\n";
$files['to_docs_json'] = $files['cdd-php'];
$files['from_openapi'] = $files['cdd-php'];
$files['php.ini'] = "html_errors = 0\ndisplay_errors = stderr\nmemory_limit = 512M\n";

file_put_contents($finalFile, json_encode($files));
echo "Built WASM bundle JSON to $finalFile\n";
