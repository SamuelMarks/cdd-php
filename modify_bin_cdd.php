<?php

$lines = file('bin_cdd_php.php');
foreach ($lines as $i => &$line) {
    if (strpos($line, '$openapi = [') !== false && strpos($lines[$i+1] ?? '', "'openapi' => '3.2.0',") !== false) {
        // Find where we emit to_openapi
    }
}
// Actually let's just do a str_replace on file_get_contents.

$code = file_get_contents('bin_cdd_php.php');

$code = str_replace(
    '$outStr = json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";',
    '$options = []; if (in_array("--swagger2", $argv)) { $options["target_version"] = "2.0"; }
        $outStr = \Cdd\Openapi\emit($openapi, null, $options) . "\n";',
    $code
);

// We also need to fix sync command
$code = str_replace(
    '$json = \Cdd\Openapi\emit($openapi, $dir);',
    '$options = []; if (in_array("--swagger2", $argv)) { $options["target_version"] = "2.0"; }
        $json = \Cdd\Openapi\emit($openapi, $dir, $options);',
    $code
);

file_put_contents('bin_cdd_php.php', $code);
echo "Done updating bin_cdd_php.php";

