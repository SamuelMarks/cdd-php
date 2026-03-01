<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../src'));
$total = 0;
$documented = 0;
foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $code = file_get_contents($file->getPathname());
        $tokens = token_get_all($code);
        $lastDoc = null;
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if (is_array($token)) {
                if ($token[0] === T_DOC_COMMENT) {
                    $lastDoc = $token[1];
                } elseif (in_array($token[0], [T_FUNCTION, T_CLASS, T_INTERFACE])) {
                    // Ignore ::class
                    $k = $i - 1;
                    while ($k >= 0 && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) $k--;
                    if ($k >= 0 && is_array($tokens[$k]) && $tokens[$k][0] === T_DOUBLE_COLON) {
                        $lastDoc = null;
                        continue;
                    }
                    
                    $j = $i + 1;
                    while ($j < count($tokens) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
                    if (!(!is_array($tokens[$j]) && $tokens[$j] === '(')) { // not a closure
                        $total++;
                        if ($lastDoc !== null) {
                            $documented++;
                        } else {
                            $name = is_array($tokens[$j]) ? $tokens[$j][1] : '?';
                            echo "Undocumented: " . $file->getPathname() . " -> " . $name . "\n";
                        }
                    }
                    $lastDoc = null;
                } elseif (!in_array($token[0], [T_DOC_COMMENT, T_WHITESPACE, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_ABSTRACT])) {
                    $lastDoc = null;
                }
            } else {
                $lastDoc = null;
            }
        }
    }
}
$percent = $total > 0 ? round(($documented / $total) * 100) : 100;
echo "Doc Coverage: $percent%\n";
file_put_contents(__DIR__ . '/../doc_cov.txt', $percent);
