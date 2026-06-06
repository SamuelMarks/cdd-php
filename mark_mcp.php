<?php

$file = 'mcp-1.0.0/client-cli.md';
$lines = file($file);
$updates = [
    'SSE Endpoint Generation' => ['to' => 'x', 'from' => 'x'],
    'HTTP Request/Auth Bridging' => ['to' => 'x', 'from' => 'x'],
    'Server-Sent Events (sse)' => ['to' => 'x', 'from' => 'x'],
];

foreach ($lines as &$line) {
    if (preg_match('/^\|\s*(\S[^\|]+?)\s*\|/', $line, $m)) {
        $feature = trim($m[1]);
        if (isset($updates[$feature])) {
            $to = $updates[$feature]['to'];
            $from = $updates[$feature]['from'];
            $line = preg_replace('/`\[.\]`\s*,\s*`\[.\]`/', "`[$to]` , `[$from]`", $line, 1);
        }
    }
}
file_put_contents($file, implode("", $lines));
echo "Updated $file\n";
