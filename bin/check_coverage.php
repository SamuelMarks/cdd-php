<?php
if (!function_exists('phpdbg_start_oplog')) {
    echo "Please run with phpdbg: phpdbg -qrr bin/check_coverage.php\n";
    exit(1);
}

phpdbg_start_oplog();
$dir = __DIR__ . '/../tests';
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . '/../tests/framework/Runner.php';

$srcDir = realpath(__DIR__ . '/../src');
// Load all src files
$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcDir));
foreach ($files as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
        require_once $file->getPathname();
    }
}

// Find all test files
$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
$testFiles = [];
foreach ($files as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
        $testFiles[] = $file->getPathname();
    }
}

$passed = 0;
$failed = 0;

ob_start();
foreach ($testFiles as $file) {
    require_once $file;
    $classes = get_declared_classes();
    $testClass = end($classes);

    if (strpos($testClass, 'Cdd\Tests') === 0 && method_exists($testClass, 'runAll')) {
        $instance = new $testClass();
        list($p, $f) = $instance->runAll();
        $passed += $p;
        $failed += $f;
    }
}
ob_end_clean();

$oplog = phpdbg_end_oplog();
$executable = phpdbg_get_executable();

$totalExecutableLines = 0;
$totalCoveredLines = 0;
$uncoveredFiles = [];

foreach ($executable as $file => $lines) {
    if (strpos($file, $srcDir) === 0) {
        $coveredInFile = $oplog[$file] ?? [];
        foreach ($lines as $line => $foo) {
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
    if ($percent == 100) {
        echo "100\n";
        exit(0);
    } else {
        echo "Coverage is $percent% ($totalCoveredLines / $totalExecutableLines)\n";
        foreach ($uncoveredFiles as $f => $lines) {
            echo "Uncovered in $f: " . implode(', ', $lines) . "\n";
        }
        exit(1);
    }
} else {
    echo "0\n";
    exit(1);
}
