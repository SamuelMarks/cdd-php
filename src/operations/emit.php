<?php

/**
 * Emits PHP controller method code from OpenAPI Operation Objects.
 *
 * @package Cdd\Operations
 */

declare(strict_types=1);

namespace Cdd\Operations;

/**
 * Emits a PHP controller method representation for an OpenAPI Operation Object.
 *
 * @param array<string, mixed> $operation The OpenAPI Operation Object.
 * @param string $path Optional URL path pattern.
 * @param string $method Optional HTTP method.
 * @param bool $asInvokable Whether to emit the method as `__invoke`.
 * @return string The generated PHP method source code.
 */
function emit(array $operation, string $path = '', string $method = '', bool $asInvokable = false): string
{
    $operationId = (string) ($operation['operationId'] ?? 'unnamedOperation');
    $methodName = $asInvokable ? '__invoke' : \Cdd\Parameters\sanitize_var_name($operationId);

    $docBlock = '';
    $hasDoc = false;
    $docStr = "/**
";
    if (isset($operation['summary'])) {
        $docStr .= " * " . \Cdd\Docstrings\sanitize_docblock_text((string) $operation['summary']) . "
 *
";
        $hasDoc = true;
    }
    if (isset($operation['description'])) {
        foreach (explode("
", \Cdd\Docstrings\sanitize_docblock_text((string) $operation['description'])) as $line) {
            $docStr .= " * " . trim($line) . "
";
        }
        $docStr .= " *
";
        $hasDoc = true;
    }
    if (isset($operation['tags'])) {
        $docStr .= " * @tags " . \Cdd\Docstrings\sanitize_docblock_text(implode(',', $operation['tags'])) . "
";
        $hasDoc = true;
    }
    if (isset($operation['externalDocs']['url'])) {
        $extUrl = \Cdd\Docstrings\sanitize_docblock_text((string) $operation['externalDocs']['url']);
        $extDesc = \Cdd\Docstrings\sanitize_docblock_text((string) ($operation['externalDocs']['description'] ?? ''));
        $docStr .= " * @externalDocs " . $extUrl . " " . $extDesc . "
";
        $hasDoc = true;
    }
    if (isset($operation['callbacks'])) {
        foreach ($operation['callbacks'] as $name => $cb) {
            $docStr .= " * @oas-callback " . $name . " " . json_encode($cb) . "
";
            $hasDoc = true;
        }
    }
    if (isset($operation['parameters']) && is_array($operation['parameters'])) {
        foreach ($operation['parameters'] as $param) {
            $rawName = (string) ($param['name'] ?? 'param');
            $varName = \Cdd\Parameters\sanitize_var_name($rawName);
            $in = (string) ($param['in'] ?? 'query');
            $pType = 'mixed';
            if (isset($param['schema']['type'])) {
                $pType = \Cdd\Schemas\openapi_type_to_php((string) $param['schema']['type'], 'mixed');
            } elseif (isset($param['schema']['$ref'])) {
                $parts = explode('/', (string) $param['schema']['$ref']);
                $pType = (string) end($parts);
            }
            $req = (bool) ($param['required'] ?? false) || $in === 'path';
            if (!$req && $pType !== 'mixed') {
                $pType .= '|null';
            }
            $meta = "[in={$in}";
            if ($rawName !== $varName) {
                $meta .= ";name={$rawName}";
            }
            $meta .= ']';
            $pDesc = isset($param['description']) && is_string($param['description'])
                ? ' ' . \Cdd\Docstrings\sanitize_docblock_text($param['description'])
                : '';
            $docStr .= " * @param {$pType} \${$varName} {$meta}{$pDesc}\n";
            $hasDoc = true;
        }
    }
    if (isset($operation['responses']) && is_array($operation['responses'])) {
        foreach ($operation['responses'] as $code => $resp) {
            if (isset($resp['links'])) {
                foreach ($resp['links'] as $linkName => $linkObj) {
                    $docStr .= " * @oas-link " . $code . " " . $linkName . " " . json_encode($linkObj) . "
";
                    $hasDoc = true;
                }
            }
        }
        $nonDefaultResp = false;
        foreach ($operation['responses'] as $code => $resp) {
            $desc = (string) ($resp['description'] ?? '');
            if ((string) $code !== '200' || ($desc !== '' && $desc !== 'Success' && $desc !== 'OK') || isset($resp['headers'])) {
                $nonDefaultResp = true;
                break;
            }
        }
        if ($nonDefaultResp) {
            $docStr .= \Cdd\Responses\emit($operation['responses'], true);
            $hasDoc = true;
        }
    }
    if ($hasDoc) {
        $docStr .= " */
";
        $docBlock = $docStr;
    }

    $paramsOut = [];
    if (isset($operation['parameters']) && is_array($operation['parameters'])) {
        foreach ($operation['parameters'] as $param) {
            $paramsOut[] = \Cdd\Parameters\emit($param);
        }
    }

    if (isset($operation['requestBody'])) {
        $paramsOut[] = \Cdd\RequestBodies\emit($operation['requestBody'], 'body');
    }

    $signature = "public function $methodName(" . implode(', ', $paramsOut) . ")";

    $returnType = '';
    $primarySchema = null;
    if (isset($operation['responses']['200']['content']['application/json']['schema'])) {
        $schema = $operation['responses']['200']['content']['application/json']['schema'];
        if (isset($schema['type'])) {
            $returnType = \Cdd\Schemas\openapi_type_to_php((string) $schema['type'], '');
            if ($schema['type'] === 'array' && isset($schema['items']['$ref'])) {
                $parts = explode('/', (string) $schema['items']['$ref']);
                $primarySchema = (string) end($parts);
            }
        } elseif (isset($schema['$ref'])) {
            $parts = explode('/', (string) $schema['$ref']);
            $returnType = (string) end($parts);
            $primarySchema = $returnType;
        }
    }

    if ($returnType !== '' && !$asInvokable) {
        $signature .= ": $returnType";
    }

    $implementation = "    // Implementation
";
    if (isset($operation['security']) && is_array($operation['security'])) {
        $implementation .= \Cdd\Security\emit($operation['security']);
    }
    if ($operationId === 'mcp_sse') {
        $implementation = "    header('Content-Type: text/event-stream');\n";
        $implementation .= "    header('Cache-Control: no-cache');\n";
        $implementation .= "    header('Connection: keep-alive');\n";
        $implementation .= "    echo 'event: endpoint' . \"\\n\" . 'data: /mcp/message' . \"\\n\\n\";\n";
        $implementation .= "    flush();\n";
    } elseif ($operationId === 'mcp_message') {
        $implementation = "    // Parse incoming MCP JSON-RPC message and proxy to local controllers
";
        $implementation .= "    \$req = json_decode(file_get_contents('php://input'), true);
";
        $implementation .= "    if (!\$req) return ['error' => ['code' => -32700, 'message' => 'Parse error']];
";
        $implementation .= "    if (isset(\$req['method']) && \$req['method'] === 'tools/call') {
";
        $implementation .= "        \$toolName = \$req['params']['name'] ?? '';
";
        $implementation .= "        if (method_exists(\$this, \$toolName)) {
";
        $implementation .= "            \$args = \$req['params']['arguments'] ?? [];
";
        $implementation .= "            try {
";
        $implementation .= "                \$res = \$this->\$toolName(\$args);
";
        $implementation .= "                return ['jsonrpc' => '2.0', 'id' => \$req['id'], 'result' => ['content' => [['type' => 'text', 'text' => json_encode(\$res)]]]];
";
        $implementation .= "            } catch (\Throwable \$e) {
";
        $implementation .= "                return ['jsonrpc' => '2.0', 'id' => \$req['id'], 'error' => ['code' => -32000, 'message' => \$e->getMessage()]];
";
        $implementation .= "            }
";
        $implementation .= "        }
";
        $implementation .= "    }
";
        $implementation .= "    return ['jsonrpc' => '2.0', 'id' => \$req['id'] ?? null, 'error' => ['code' => -32601, 'message' => 'Method not found']];
";
    } elseif ($primarySchema !== null && $primarySchema !== '') {
        $exportedSchema = var_export($primarySchema, true);
        $lowerMethod = strtolower($method);
        $rawIdVar = 'id';
        if (preg_match('/\{([^}]+)\}/', $path, $matches)) {
            $rawIdVar = $matches[1];
        }
        $idVar = \Cdd\Parameters\sanitize_var_name($rawIdVar);
        if ($lowerMethod === 'get') {
            if (strpos($path, '{') !== false) {
                $implementation .= "    \$dao = \Api\Daos\DaoFactory::create({$exportedSchema});
";
                $implementation .= "    \$record = \$dao->getById(\${$idVar});
";
                $implementation .= "    if (!\$record) {
";
                $implementation .= "        header('HTTP/1.1 404 Not Found');
";
                $implementation .= "        echo json_encode(['error' => 'Not found']);
";
                $implementation .= "        return;
";
                $implementation .= "    }
";
                $implementation .= "    header('Content-Type: application/json');
";
                $implementation .= "    echo json_encode(\$record->toArray());
";
            } else {
                $implementation .= "    \$dao = \Api\Daos\DaoFactory::create({$exportedSchema});
";
                $implementation .= "    \$records = \$dao->getAll();
";
                $implementation .= "    header('Content-Type: application/json');
";
                $implementation .= "    echo json_encode(array_map(fn(\$r) => \$r->toArray(), \$records));
";
            }
        } elseif ($lowerMethod === 'post') {
            $implementation .= "    \$dao = \Api\Daos\DaoFactory::create({$exportedSchema});
";
            $implementation .= "    \$record = \$dao->create(json_decode(file_get_contents('php://input'), true) ?? []);
";
            $implementation .= "    header('Content-Type: application/json');
";
            $implementation .= "    echo json_encode(\$record->toArray());
";
        } elseif ($lowerMethod === 'put' || $lowerMethod === 'patch') {
            $implementation .= "    \$dao = \Api\Daos\DaoFactory::create({$exportedSchema});
";
            $implementation .= "    \$record = \$dao->update(\${$idVar}, json_decode(file_get_contents('php://input'), true) ?? []);
";
            $implementation .= "    if (!\$record) {
";
            $implementation .= "        header('HTTP/1.1 404 Not Found');
";
            $implementation .= "        echo json_encode(['error' => 'Not found']);
";
            $implementation .= "        return;
";
            $implementation .= "    }
";
            $implementation .= "    header('Content-Type: application/json');
";
            $implementation .= "    echo json_encode(\$record->toArray());
";
        } elseif ($lowerMethod === 'delete') {
            $implementation .= "    \$dao = \Api\Daos\DaoFactory::create({$exportedSchema});
";
            $implementation .= "    \$success = \$dao->delete(\${$idVar});
";
            $implementation .= "    if (!\$success) {
";
            $implementation .= "        header('HTTP/1.1 404 Not Found');
";
            $implementation .= "        echo json_encode(['error' => 'Not found']);
";
            $implementation .= "        return;
";
            $implementation .= "    }
";
            $implementation .= "    header('Content-Type: application/json');
";
            $implementation .= "    echo json_encode(['success' => true]);
";
        }
    }

    return $docBlock . $signature . " {
" . $implementation . "}
";
}
