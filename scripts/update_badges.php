<?php

$rootDir = dirname(__DIR__);
$readmePath = $rootDir . '/README.md';

if (!file_exists($readmePath)) {
    exit(1);
}

function getColor($pct)
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
$testOutput = shell_exec("phpdbg -qrr bin/check_coverage.php 2>/dev/null");
$testCov = 0;
if (preg_match('/([0-9.]+)/', $testOutput, $matches)) {
    $testCov = (int) $matches[1];
}

// Doc coverage
$docOutput = shell_exec("php bin/check_docs.php 2>/dev/null");
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
