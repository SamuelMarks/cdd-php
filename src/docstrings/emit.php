<?php

declare(strict_types=1);

namespace Cdd\Docstrings;

/**
 * Emits a PHP docstring from an array.
 */
function emit(array $doc): string {
    $out = "/**
";
    
    if (!empty($doc['description'])) {
        $lines = explode("
", $doc['description']);
        foreach ($lines as $line) {
            $out .= " * " . $line . "
";
        }
    }
    
    if (!empty($doc['tags'])) {
        foreach ($doc['tags'] as $tag => $data) {
            if ($tag === 'param') {
                foreach ($data as $param) {
                    $out .= " * @param {$param['type']} {$param['name']}
";
                }
            } elseif ($tag === 'return') {
                $out .= " * @return {$data['type']} {$data['description']}
";
            } else {
                foreach ($data as $val) {
                    $out .= " * @$tag " . trim($val) . "
";
                }
            }
        }
    }
    
    $out .= " */
";
    return $out;
}
