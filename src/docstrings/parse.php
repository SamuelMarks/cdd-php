<?php

declare(strict_types=1);

namespace Cdd\Docstrings;

/**
 * Parses a PHP docstring into its component parts.
 */
function parse(string $docComment): array {
    $parsed = [
        'description' => '',
        'tags' => []
    ];
    
    if (empty($docComment)) {
        return $parsed;
    }
    
    // Clean up comment asterisks
    $lines = explode("
", $docComment);
    $descriptionLines = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Remove '/**' or '*/' or '*'
        $line = preg_replace('/^\/?\**\/? ?/', '', $line);
        
        if (str_starts_with($line, '@')) {
            // It's a tag
            $parts = preg_split('/\s+/', $line, 3);
            $tag = substr($parts[0], 1);
            $type = $parts[1] ?? '';
            $desc = $parts[2] ?? '';
            
            if ($tag === 'param') {
                $parsed['tags'][$tag][] = ['type' => $type, 'name' => $desc];
            } elseif ($tag === 'return') {
                $parsed['tags'][$tag] = ['type' => $type, 'description' => $desc];
            } else {
                $parsed['tags'][$tag][] = $type . ' ' . $desc;
            }
        } else {
            if ($line !== '') {
                $descriptionLines[] = $line;
            }
        }
    }
    
    $parsed['description'] = implode("
", $descriptionLines);
    return $parsed;
}
