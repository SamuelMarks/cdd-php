<?php

$outDir = dirname($argv[1] ?? 'build/cdd-php');
if (!is_dir($outDir)) mkdir($outDir, 0777, true);

$finalFile = $argv[1] ?? 'build/cdd-php';
$pharFile = $finalFile . '.phar';

if (file_exists($pharFile)) unlink($pharFile);
if (file_exists($finalFile)) unlink($finalFile);

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

// Create a self-extracting PHP archive.
$code = "#!/usr/bin/env php\n<?php\n";
$code .= "\$tmp = sys_get_temp_dir() ?: '/tmp';\n";
$code .= "\$extractDir = \$tmp . '/cdd-php-' . md5(__FILE__);\n";
$code .= "if (!@mkdir(\$extractDir, 0777, true) && !is_dir(\$extractDir)) {\n";
$code .= "    \$extractDir = __DIR__ . '/.cdd-php-cache-' . md5(__FILE__);\n";
$code .= "    if (!@mkdir(\$extractDir, 0777, true) && !is_dir(\$extractDir)) {\n";
$code .= "        die(\"Error: Cannot create extraction directory. Please ensure /tmp or current directory is writable in WASI.\");\n";
$code .= "    }\n";
$code .= "}\n";
$code .= "\$versionFile = \$extractDir . '/.extracted_version';\n";
$code .= "\$currentVersion = md5(serialize(array_keys(" . var_export(array_keys($files), true) . ")) . md5(__FILE__) . filemtime(__FILE__));\n";
$code .= "if (!file_exists(\$versionFile) || file_get_contents(\$versionFile) !== \$currentVersion) {\n";
$code .= "    \$files = " . var_export($files, true) . ";\n";
$code .= "    foreach (\$files as \$path => \$content) {\n";
$code .= "        \$fullPath = \$extractDir . '/' . \$path;\n";
$code .= "        \$dir = dirname(\$fullPath);\n";
$code .= "        if (!is_dir(\$dir)) mkdir(\$dir, 0777, true);\n";
$code .= "        file_put_contents(\$fullPath, \$content);\n";
$code .= "    }\n";
$code .= "    file_put_contents(\$versionFile, \$currentVersion);\n";
$code .= "}\n";
$code .= "require \$extractDir . '/bin/cdd-php';\n";

file_put_contents($finalFile, $code);
chmod($finalFile, 0755);
echo "Built self-extracting PHP to $finalFile\n";
