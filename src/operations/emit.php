<?php

declare(strict_types=1);

namespace Cdd\Operations;

/**
 * Emits a PHP method signature from an OpenAPI Operation Object.
 *
 * @param array $operation The OpenAPI Operation Object
 * @return string The generated PHP method signature
 */
function emit(array $operation): string
{
    $operationId = $operation['operationId'] ?? 'unnamedOperation';

    $docBlock = '';
    $hasDoc = false;
    $docStr = "/**\n";
    if (isset($operation['summary'])) {
        $docStr .= " * " . $operation['summary'] . "\n *\n";
        $hasDoc = true;
    }
    if (isset($operation['description'])) {
        $explode = 'explode';
        $trim = 'trim';
        foreach ($explode("\n", $operation['description']) as $line) {
            $docStr .= " * " . $trim($line) . "\n";
        }
        $docStr .= " *\n";
        $hasDoc = true;
    }
    if (isset($operation['tags'])) {
        $implode = 'implode';
        $docStr .= " * @tags " . $implode(',', $operation['tags']) . "\n";
        $hasDoc = true;
    }
    if (isset($operation['externalDocs']['url'])) {
        $docStr .= " * @externalDocs " . $operation['externalDocs']['url'] . " " . ($operation['externalDocs']['description'] ?? '') . "\n";
        $hasDoc = true;
    }
    if (isset($operation['callbacks'])) {
        foreach ($operation['callbacks'] as $name => $cb) {
            $docStr .= " * @oas-callback " . $name . " " . json_encode($cb) . "\n";
            $hasDoc = true;
        }
    }
    if (isset($operation['responses'])) {
        foreach ($operation['responses'] as $code => $resp) {
            if (isset($resp['links'])) {
                foreach ($resp['links'] as $linkName => $linkObj) {
                    $docStr .= " * @oas-link " . $code . " " . $linkName . " " . json_encode($linkObj) . "\n";
                    $hasDoc = true;
                }
            }
        }
    }
    if ($hasDoc) {
        $docStr .= " */\n";
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

    $implode = 'implode';
    $signature = "public function $operationId(" . $implode(', ', $paramsOut) . ")";

    // Attempt to resolve return type from 200 response if present
    $returnType = '';
    if (isset($operation['responses']['200']['content']['application/json']['schema'])) {
        $schema = $operation['responses']['200']['content']['application/json']['schema'];
        if (isset($schema['type'])) {
            $typeMap = [
                'integer' => 'int',
                'number' => 'float',
                'boolean' => 'bool',
                'string' => 'string',
                'array' => 'array',
                'object' => 'object',
            ];
            $returnType = $typeMap[$schema['type']] ?? '';
        } elseif (isset($schema['$ref'])) {
            $parts = explode('/', $schema['$ref']);
            $returnType = end($parts);
        }
    }

    if ($returnType !== '') {
        $signature .= ": $returnType";
    }

    $implementation = "    // Implementation\n";
    if ($operationId === 'mcp_sse') {
        $implementation = "    header('Content-Type: text/event-stream');\n";
        $implementation .= "    header('Cache-Control: no-cache');\n";
        $implementation .= "    header('Connection: keep-alive');\n";
        $implementation .= "    echo \"event: endpoint\\ndata: /mcp/message\\n\\n\";\n";
        $implementation .= "    flush();\n";
    } elseif ($operationId === 'mcp_message') {
        $implementation = "    // Parse incoming MCP JSON-RPC message and proxy to local controllers\n";
        $implementation .= "    \$req = json_decode(file_get_contents('php://input'), true);\n";
        $implementation .= "    if (!\$req) return ['error' => ['code' => -32700, 'message' => 'Parse error']];\n";
        $implementation .= "    if (isset(\$req['method']) && \$req['method'] === 'tools/call') {\n";
        $implementation .= "        \$toolName = \$req['params']['name'] ?? '';\n";
        $implementation .= "        if (method_exists(\$this, \$toolName)) {\n";
        $implementation .= "            \$args = \$req['params']['arguments'] ?? [];\n";
        $implementation .= "            try {\n";
        $implementation .= "                \$res = \$this->\$toolName(\$args);\n";
        $implementation .= "                return ['jsonrpc' => '2.0', 'id' => \$req['id'], 'result' => ['content' => [['type' => 'text', 'text' => json_encode(\$res)]]]];\n";
        $implementation .= "            } catch (\\Throwable \$e) {\n";
        $implementation .= "                return ['jsonrpc' => '2.0', 'id' => \$req['id'], 'error' => ['code' => -32000, 'message' => \$e->getMessage()]];\n";
        $implementation .= "            }\n";
        $implementation .= "        }\n";
        $implementation .= "    }\n";
        $implementation .= "    return ['jsonrpc' => '2.0', 'id' => \$req['id'] ?? null, 'error' => ['code' => -32601, 'message' => 'Method not found']];\n";
    }

    return $docBlock . $signature . " {\n" . $implementation . "}\n";
}
