<?php

/**
 * Builds the standalone cdd-php PHAR archive with stripped runtime sources
 * and minimal PSR-4 autoloading.
 *
 * @package Cdd\Scripts
 */

declare(strict_types=1);

require_once __DIR__ . '/build_wasm_bundle.php';

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $finalFile = $argv[1] ?? 'build/cdd-php';
    $outDir = dirname($finalFile);
    if (!is_dir($outDir)) {
        mkdir($outDir, 0777, true);
    }

    $pharFile = $finalFile . '.phar';

    if (file_exists($pharFile)) {
        unlink($pharFile);
    }
    if (file_exists($finalFile)) {
        unlink($finalFile);
    }

    $baseDir = dirname(__DIR__);
    $directory = new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($directory);

    $phar = new Phar($pharFile);
    $phar->startBuffering();

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || $file->isDir()) {
            continue;
        }
        $path = $file->getPathname();
        $rel = substr($path, strlen($baseDir) + 1);
        $rel = str_replace('\\', '/', $rel);

        if (is_runtime_bundle_file($rel)) {
            $stripped = php_strip_whitespace($path);
            if ($rel === 'bin/cdd-php') {
                $stripped = (string) preg_replace('/^#![^\r\n]+\r?\n/', '', $stripped);
            }
            $phar->addFromString($rel, $stripped);
        }
    }

    $phar->addFromString('vendor/autoload.php', build_minimal_autoload_php());

    $stub = "#!/usr/bin/env php\n<?php\nPhar::mapPhar('cdd-php.phar');\nrequire 'phar://cdd-php.phar/bin/cdd-php';\n__HALT_COMPILER();\n";
    $phar->setStub($stub);
    $phar->stopBuffering();

    copy($pharFile, $finalFile);
    chmod($finalFile, 0755);
    echo "Built PHAR to $pharFile and copied to $finalFile\n";
}
