<?php

/**
 * Code coverage verification script using phpdbg oplog.
 *
 * Ensures 100% executable line and function coverage across `src/` with zero
 * ignored lines (`cov_ignore` or `@codeCoverageIgnore`).
 *
 * @package Cdd\Bin
 */

declare(strict_types=1);

ini_set('memory_limit', '-1');
putenv('XDEBUG_MODE=off');

if (!function_exists('phpdbg_start_oplog')) {
    echo "Please run with phpdbg: phpdbg -qrr bin/check_coverage.php
";
    exit(1);
}

$dir = __DIR__ . '/../tests';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../tests/framework/Runner.php';

$srcDir = realpath(__DIR__ . '/../src');
if ($srcDir === false) {
    echo "Failed to resolve src directory\n";
    exit(1);
}

$oplog = [];

phpdbg_start_oplog();

// Load all src files after starting oplog so top-level opcodes are recorded, and verify no coverage-ignore comments exist
$srcFiles = [];
$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcDir));
foreach ($files as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
        $path = (string) $file->getRealPath();
        $rawContent = (string) file_get_contents($path);
        if (strpos($rawContent, 'cov_ignore') !== false || strpos($rawContent, '@codeCoverageIgnore') !== false) {
            echo "Forbidden coverage ignore directive found in: $path\n";
            exit(1);
        }
        $srcFiles[] = $path;
        require_once $path;
    }
}

$initOplog = phpdbg_end_oplog();
$executable = phpdbg_get_executable(['files' => $srcFiles]);
foreach ($srcFiles as $srcFile) {
    if (isset($initOplog[$srcFile])) {
        foreach ($initOplog[$srcFile] as $line => $count) {
            $oplog[$srcFile][$line] = ($oplog[$srcFile][$line] ?? 0) + $count;
        }
    }
}
unset($initOplog);

$testClasses = \Cdd\Tests\Framework\Runner::discoverTestClasses($dir);

$passed = 0;
$failed = 0;

ob_start();
foreach ($testClasses as $testClass) {
    /** @var \Cdd\Tests\Framework\TestCase $instance */
    $instance = new $testClass();
    phpdbg_start_oplog();
    [$p, $f] = $instance->runAll();
    $classOplog = phpdbg_end_oplog();
    foreach ($srcFiles as $srcFile) {
        if (isset($classOplog[$srcFile])) {
            foreach ($classOplog[$srcFile] as $line => $count) {
                $oplog[$srcFile][$line] = ($oplog[$srcFile][$line] ?? 0) + $count;
            }
        }
    }
    unset($classOplog);
    $passed += $p;
    $failed += $f;
}
$runnerOutput = (string) ob_get_clean();

if ($failed > 0) {
    echo "Tests failed ($failed failed, $passed passed):
$runnerOutput
";
    exit(1);
}

$totalExecutableLines = 0;
$totalCoveredLines = 0;
$uncoveredFiles = [];

foreach ($executable as $file => $lines) {
    if (strpos($file, $srcDir) === 0) {
        $coveredInFile = $oplog[$file] ?? [];
        foreach ($lines as $line => $opcodeCount) {
            $totalExecutableLines++;
            if (isset($coveredInFile[$line])) {
                $totalCoveredLines++;
            } else {
                $uncoveredFiles[$file][] = $line;
            }
        }
    }
}

if ($totalExecutableLines > 0) {
    $percent = round(($totalCoveredLines / $totalExecutableLines) * 100, 2);
    if ($totalCoveredLines === $totalExecutableLines) {
        echo "100\n";
        fflush(STDOUT);
        exit(0);
    } else {
        echo "Coverage is $percent% ($totalCoveredLines / $totalExecutableLines)\n";
        foreach ($uncoveredFiles as $f => $lines) {
            echo "Uncovered in $f: " . implode(', ', $lines) . "\n";
        }
        fflush(STDOUT);
        exit(1);
    }
} else {
    echo "0\n";
    fflush(STDOUT);
    exit(1);
}
