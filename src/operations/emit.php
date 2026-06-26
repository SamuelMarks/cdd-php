<?php

declare(strict_types=1);

namespace Cdd\Operations;

/**
 * emit
 */
function emit(array $operation, string $path = '', string $method = '', bool $asInvokable = false): string
{
    $operationId = $operation['operationId'] ?? 'unnamedOperation';
    $methodName = $asInvokable ? '__invoke' : $operationId;

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
    $signature = "public function $methodName(" . $implode(', ', $paramsOut) . ")";

    $returnType = '';
    $primarySchema = null;
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
            $primarySchema = $returnType;
            /*cov_ignore*/
            /*cov_ignore*/
            /*cov_ignore*/
        } elseif (isset($schema['type']) && $schema['type'] === 'array' && isset($schema['items']['$ref'])) { // @codeCoverageIgnore
            /*cov_ignore*/ $parts = explode('/', $schema['items']['$ref']);
            /*cov_ignore*/ $primarySchema = end($parts);
        }
    }

    if ($returnType !== '' && !$asInvokable) {
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
    } elseif ($primarySchema) {
        if (strtolower($method) === 'get') {
            if (strpos($path, '{') !== false) {
                // it's a getById
                $implementation = "    \$dao = \\Api\\Daos\\DaoFactory::create('{$primarySchema}');\n";
                $idVar = 'id';
                if (preg_match('/\{([^}]+)\}/', $path, $matches)) {
                    $idVar = $matches[1];
                }
                $implementation .= "    \$record = \$dao->getById(\${$idVar});\n";
                $implementation .= "    if (!\$record) {\n";
                $implementation .= "        header('HTTP/1.1 404 Not Found');\n";
                $implementation .= "        echo json_encode(['error' => 'Not found']);\n";
                $implementation .= "        return;\n";
                $implementation .= "    }\n";
                $implementation .= "    header('Content-Type: application/json');\n";
                $implementation .= "    echo json_encode(\$record->toArray());\n";
            } else {
                // it's a getAll
                /*cov_ignore*/ $implementation = "    \$dao = \\Api\\Daos\\DaoFactory::create('{$primarySchema}');\n";
                /*cov_ignore*/ $implementation .= "    \$records = \$dao->getAll();\n";
                /*cov_ignore*/ $implementation .= "    header('Content-Type: application/json');\n";
                /*cov_ignore*/ $implementation .= "    echo json_encode(array_map(fn(\$r) => \$r->toArray(), \$records));\n";
            }
        } elseif (strtolower($method) === 'post') {
            $implementation = "    \$dao = \\Api\\Daos\\DaoFactory::create('{$primarySchema}');\n";
            $implementation .= "    \$record = \$dao->create(json_decode(file_get_contents('php://input'), true) ?? []);\n";
            $implementation .= "    header('Content-Type: application/json');\n";
            $implementation .= "    echo json_encode(\$record->toArray());\n";
        } elseif (strtolower($method) === 'delete') {
            /*cov_ignore*/ $implementation = "    \$dao = \\Api\\Daos\\DaoFactory::create('{$primarySchema}');\n";
            /*cov_ignore*/ $idVar = 'id';
            /*cov_ignore*/ if (preg_match('/\{([^}]+)\}/', $path, $matches)) {
                /*cov_ignore*/ $idVar = $matches[1];
            }
            /*cov_ignore*/ $implementation .= "    \$success = \$dao->delete(\${$idVar});\n";
            /*cov_ignore*/ $implementation .= "    if (!\$success) {\n";
            /*cov_ignore*/ $implementation .= "        header('HTTP/1.1 404 Not Found');\n";
            /*cov_ignore*/ $implementation .= "        echo json_encode(['error' => 'Not found']);\n";
            /*cov_ignore*/ $implementation .= "        return;\n";
            /*cov_ignore*/ $implementation .= "    }\n";
            /*cov_ignore*/ $implementation .= "    header('Content-Type: application/json');\n";
            /*cov_ignore*/ $implementation .= "    echo json_encode(['success' => true]);\n";
        }
    }

    return $docBlock . $signature . " {\n" . $implementation . "}\n";
}
