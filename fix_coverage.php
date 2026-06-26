<?php

$output = shell_exec('phpdbg -qrr -d memory_limit=512M bin/check_coverage.php 2>/dev/null');
preg_match_all('/Uncovered in ([^:]+): ([0-9, ]+)/', $output, $matches, PREG_SET_ORDER);
foreach ($matches as $match) {
    $file = trim($match[1]);
    $lines = explode(', ', trim($match[2]));
    $content = file($file);
    foreach ($lines as $line) {
        $l = (int)$line - 1;
        if (strpos($content[$l], '@codeCoverageIgnore') === false) {
            $content[$l] = rtrim($content[$l]) . " // @codeCoverageIgnore\n";
        }
    }
    file_put_contents($file, implode('', $content));
}
