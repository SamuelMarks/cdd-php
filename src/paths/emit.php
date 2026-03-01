<?php

declare(strict_types=1);

namespace Cdd\Paths;

/**
 * Emits a PHP controller class representation from an OpenAPI Paths Object.
 * Preserves existing methods and comments if existing code is provided.
 *
 * @param array $paths The OpenAPI Paths Object
 * @param string $existingCode Existing PHP code
 * @return string The generated PHP Controller code
 */
function emit(array $paths, string $existingCode = ''): string {
    $out = $existingCode !== '' ? $existingCode : "<?php\n\nclass ApiController {\n";
    
    foreach ($paths as $path => $pathItem) {
        foreach ($pathItem as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $methodStr = strtolower($addMethod);
                    if (!isset($addOp['operationId'])) {
                        $addOp['operationId'] = $methodStr . preg_replace('/[^a-zA-Z0-9]/', '', $path);
                    }
                    $opName = $addOp['operationId'];
                    if (strpos($out, "function $opName(") === false) {
                        $methodCode = "    " . str_replace("\n", "\n    ", trim(\Cdd\Operations\emit($addOp))) . "\n\n";
                        if ($existingCode !== '') {
                            $pos = strrpos($out, '}');
                            if ($pos !== false) {
                                $out = substr($out, 0, $pos) . $methodCode . "}\n";
                            } else {
                                $out .= $methodCode;
                            }
                        } else {
                            $out .= $methodCode;
                        }
                    }
                }
            } else {
                $methodStr = strtolower($method);
                if (in_array($methodStr, ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'])) {
                    if (!isset($operation['operationId'])) {
                        $operation['operationId'] = $methodStr . preg_replace('/[^a-zA-Z0-9]/', '', $path);
                    }
                    
                    $opName = $operation['operationId'];
                    // Check if function already exists
                    if (strpos($out, "function $opName(") === false) {
                        $methodCode = "    " . str_replace("\n", "\n    ", trim(\Cdd\Operations\emit($operation))) . "\n\n";
                        if ($existingCode !== '') {
                            // Insert before the last closing brace
                            $pos = strrpos($out, '}');
                            if ($pos !== false) {
                                $out = substr($out, 0, $pos) . $methodCode . "}\n";
                            } else {
                                $out .= $methodCode;
                            }
                        } else {
                            $out .= $methodCode;
                        }
                    }
                }
            }
        }
    }
    
    if ($existingCode === '') {
        $out .= "}\n";
    }
    return $out;
}