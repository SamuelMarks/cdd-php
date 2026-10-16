<?php

/**
 * Updates test and documentation coverage badges in README.md.
 *
 * @package Cdd\Scripts
 */

declare(strict_types=1);

$rootDir = dirname(__DIR__);
$readmePath = $rootDir . '/README.md';

if (!file_exists($readmePath)) {
    exit(1);
}

/**
 * Maps a coverage percentage to a shields.io badge color.
 *
 * @param int|float $pct The coverage percentage.
 * @return string The badge color string.
 */
function getColor(int|float $pct): string
{
    if ($pct >= 90) {
        return 'brightgreen';
    }
    if ($pct >= 80) {
        return 'green';
    }
    if ($pct >= 70) {
        return 'yellowgreen';
    }
    if ($pct >= 60) {
        return 'yellow';
    }
    if ($pct >= 50) {
        return 'orange';
    }
    return 'red';
}

// Test coverage
$phpBin = PHP_SAPI === 'phpdbg' ? (string) preg_replace('/phpdbg([0-9.]*)$/', 'php$1', PHP_BINARY) : PHP_BINARY;
$phpdbgBin = PHP_SAPI === 'phpdbg' ? PHP_BINARY : (string) preg_replace('/php([0-9.]*)$/', 'phpdbg$1', PHP_BINARY);
if (!is_executable($phpdbgBin)) {
    $phpdbgBin = 'phpdbg';
}
$testCmd = escapeshellarg($phpdbgBin) . ' -qrr -d memory_limit=512M -d opcache.enable=0 -d opcache.enable_cli=0 -d opcache.jit=0 ' . escapeshellarg($rootDir . '/bin/check_coverage.php') . ' 2>/dev/null';
$testOutput = (string) shell_exec($testCmd);
$testCov = 0;
if (preg_match('/^100$/m', trim($testOutput))) {
    $testCov = 100;
} elseif (preg_match('/Coverage is ([0-9.]+)/', $testOutput, $matches)) {
    $testCov = (int) $matches[1];
}

// Doc coverage
$docCmd = escapeshellarg($phpBin) . ' ' . escapeshellarg($rootDir . '/bin/check_docs.php') . ' 2>/dev/null';
$docOutput = (string) shell_exec($docCmd);
$docCov = 0;
if (preg_match('/Doc Coverage:\s*([0-9.]+)/', $docOutput, $matches)) {
    $docCov = (int) $matches[1];
}

$testColor = getColor($testCov);
$docColor = getColor($docCov);

$content = file_get_contents($readmePath);

$content = preg_replace(
    '/\[\!\[Test Coverage\]\(https:\/\/img\.shields\.io\/badge\/test_coverage-[0-9.]+%25-[a-z]+\.svg\)\]\(#\)/',
    '[![Test Coverage](https://img.shields.io/badge/test_coverage-' . $testCov . '%25-' . $testColor . '.svg)](#)',
    $content
);

$content = preg_replace(
    '/\[\!\[Doc Coverage\]\(https:\/\/img\.shields\.io\/badge\/doc_coverage-[0-9.]+%25-[a-z]+\.svg\)\]\(#\)/',
    '[![Doc Coverage](https://img.shields.io/badge/doc_coverage-' . $docCov . '%25-' . $docColor . '.svg)](#)',
    $content
);

file_put_contents($readmePath, $content);
