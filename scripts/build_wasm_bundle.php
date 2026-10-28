<?php

/**
 * Builds the minimal JSON payload bundle for WebAssembly packaging.
 *
 * Excludes dev/test-only vendor packages, strips PHP comments and whitespace,
 * generates a lightweight PSR-4 autoloader for `nikic/php-parser`, and serializes
 * the virtual filesystem with unescaped slashes.
 *
 * @package Cdd\Scripts
 */

declare(strict_types=1);

/**
 * Determines whether a relative repository path is required at runtime by cdd-php.
 *
 * @param string $rel Normalized relative path from the project root.
 * @return bool True if the file must be included in the runtime bundle.
 */
function is_runtime_bundle_file(string $rel): bool
{
    if ($rel === 'bin/cdd-php') {
        return true;
    }
    if (str_starts_with($rel, 'src/') && str_ends_with($rel, '.php')) {
        return true;
    }
    if (str_starts_with($rel, 'vendor/nikic/php-parser/lib/') && str_ends_with($rel, '.php')) {
        return true;
    }
    return false;
}

/**
 * Builds the minimal runtime `vendor/autoload.php` source for bundled builds.
 *
 * @return string Minified PHP autoloader source code.
 */
function build_minimal_autoload_php(): string
{
    return "<?php\ndeclare(strict_types=1);\n"
        . "spl_autoload_register(static function (string \$class): void {\n"
        . "    if (str_starts_with(\$class, 'PhpParser\\\\')) {\n"
        . "        \$file = __DIR__ . '/nikic/php-parser/lib/' . str_replace('\\\\', '/', \$class) . '.php';\n"
        . "        if (file_exists(\$file)) {\n"
        . "            require_once \$file;\n"
        . "        }\n"
        . "    }\n"
        . "});\n"
        . "\$baseDir = dirname(__DIR__);\n"
        . "foreach (['src/exceptions/CddException.php','src/cli/McpServer.php','src/cli/JsonRpcHttpServer.php','src/cli/ProjectSynchronizer.php','src/cli/DocsJsonGenerator.php','src/cli/CddConfig.php','src/cli/CddCli.php','src/cli/Cli.php'] as \$relFile) {\n"
        . "    \$fullPath = \$baseDir . '/' . \$relFile;\n"
        . "    if (file_exists(\$fullPath)) {\n"
        . "        require_once \$fullPath;\n"
        . "    }\n"
        . "}\n";
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $finalFile = $argv[1] ?? 'build/wasm_bundle.json';
    $outDir = dirname($finalFile);
    if (!is_dir($outDir)) {
        mkdir($outDir, 0777, true);
    }

    $baseDir = dirname(__DIR__);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
    );

    /** @var array<string, string> $files */
    $files = [];
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
            if (mb_check_encoding($stripped, 'UTF-8')) {
                $files[$rel] = $stripped;
            }
        }
    }

    ksort($files);
    $files['vendor/autoload.php'] = build_minimal_autoload_php();
    $files['cdd-php'] = "<?php\n\$argv = \$_SERVER['argv'];\narray_unshift(\$argv, 'cdd-php');\n\$argc = count(\$argv);\n\$isCli = true;\n\$GLOBALS['argv'] = \$argv;\n\$GLOBALS['argc'] = \$argc;\n\$_SERVER['argv'] = \$argv;\n\$_SERVER['argc'] = \$argc;\nrequire __DIR__ . '/bin/cdd-php';\n";
    $files['to_docs_json'] = $files['cdd-php'];
    $files['from_openapi'] = $files['cdd-php'];
    $files['php.ini'] = "html_errors = 0\ndisplay_errors = stderr\nmemory_limit = 512M\n";

    $json = json_encode($files, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        echo 'JSON encoding failed: ' . json_last_error_msg() . "\n";
        exit(1);
    }

    file_put_contents($finalFile, $json);
    echo "Built WASM bundle JSON to $finalFile\n";
}
