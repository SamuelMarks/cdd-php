<?php

$lines = file('src/openapi/parse.php');
$start = -1;
$end = -1;
foreach ($lines as $i => $line) {
    if (strpos($line, 'if (isset($data[\'swagger\']) && !isset($data[\'openapi\'])) {') !== false) {
        $start = $i;
    }
    if ($start !== -1 && strpos($line, '// openapi (string) - REQUIRED') !== false) {
        $end = $i;
        break;
    }
}

$replacement = <<<'REPLACEMENT'
    if (isset($data['swagger']) && !isset($data['openapi'])) {
        $data['openapi'] = '3.0.0';
        
        // Host, BasePath, Schemes -> Servers
        if (isset($data['host']) || isset($data['basePath']) || isset($data['schemes'])) {
            $host = $data['host'] ?? 'localhost';
            $basePath = $data['basePath'] ?? '/';
            $schemes = $data['schemes'] ?? ['http'];
            $servers = [];
            foreach ($schemes as $scheme) {
                $servers[] = ['url' => $scheme . '://' . $host . $basePath];
            }
            $data['servers'] = $servers;
            unset($data['host'], $data['basePath'], $data['schemes']);
        }

        if (!isset($data['components'])) {
            $data['components'] = [];
        }
        if (isset($data['definitions'])) {
            $data['components']['schemas'] = $data['definitions'];
            unset($data['definitions']);
        }
        if (isset($data['parameters'])) {
            $data['components']['parameters'] = $data['parameters'];
            unset($data['parameters']);
        }
        if (isset($data['responses'])) {
            $data['components']['responses'] = $data['responses'];
            unset($data['responses']);
        }
        if (isset($data['securityDefinitions'])) {
            foreach ($data['securityDefinitions'] as $name => &$scheme) {
                if ($scheme['type'] === 'basic') {
                    $scheme['type'] = 'http';
                    $scheme['scheme'] = 'basic';
                }
                if ($scheme['type'] === 'oauth2') {
                    $flowType = $scheme['flow'] ?? 'implicit';
                    if ($flowType === 'application') $flowType = 'clientCredentials';
                    if ($flowType === 'accessCode') $flowType = 'authorizationCode';
                    $flow = [];
                    if (isset($scheme['authorizationUrl'])) $flow['authorizationUrl'] = $scheme['authorizationUrl'];
                    if (isset($scheme['tokenUrl'])) $flow['tokenUrl'] = $scheme['tokenUrl'];
                    if (isset($scheme['scopes'])) $flow['scopes'] = $scheme['scopes'];
                    $scheme['flows'] = [ $flowType => $flow ];
                    unset($scheme['flow'], $scheme['authorizationUrl'], $scheme['tokenUrl'], $scheme['scopes']);
                }
            }
            $data['components']['securitySchemes'] = $data['securityDefinitions'];
            unset($data['securityDefinitions']);
        }
        
        $globalConsumes = $data['consumes'] ?? ['application/json'];
        $globalProduces = $data['produces'] ?? ['application/json'];
        unset($data['consumes'], $data['produces']);
        
        if (isset($data['paths'])) {
            foreach ($data['paths'] as $path => &$pathItem) {
                foreach ($pathItem as $method => &$operation) {
                    if (in_array(strtolower($method), ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'])) {
                        $opConsumes = $operation['consumes'] ?? $globalConsumes;
                        $opProduces = $operation['produces'] ?? $globalProduces;
                        unset($operation['consumes'], $operation['produces']);
                        
                        if (isset($operation['parameters'])) {
                            $formData = [];
                            foreach ($operation['parameters'] as $i => &$param) {
                                if (isset($param['in']) && $param['in'] === 'body') {
                                    $schema = $param['schema'] ?? [];
                                    $content = [];
                                    foreach ($opConsumes as $mime) {
                                        $content[$mime] = ['schema' => $schema];
                                    }
                                    $operation['requestBody'] = [
                                        'content' => empty($content) ? ['application/json' => ['schema' => $schema]] : $content,
                                        'required' => $param['required'] ?? false
                                    ];
                                    if (isset($param['description'])) {
                                        $operation['requestBody']['description'] = $param['description'];
                                    }
                                    unset($operation['parameters'][$i]);
                                } else if (isset($param['in']) && $param['in'] === 'formData') {
                                    $schema = ['type' => $param['type'] ?? 'string'];
                                    if (isset($param['format'])) $schema['format'] = $param['format'];
                                    $formData[$param['name']] = $schema;
                                    unset($operation['parameters'][$i]);
                                } else if (!isset($param['schema']) && isset($param['type'])) {
                                    $param['schema'] = ['type' => $param['type']];
                                    if (isset($param['format'])) $param['schema']['format'] = $param['format'];
                                    if (isset($param['items'])) $param['schema']['items'] = $param['items'];
                                    if (isset($param['default'])) $param['schema']['default'] = $param['default'];
                                    if (isset($param['maximum'])) $param['schema']['maximum'] = $param['maximum'];
                                    if (isset($param['exclusiveMaximum'])) $param['schema']['exclusiveMaximum'] = $param['exclusiveMaximum'];
                                    if (isset($param['minimum'])) $param['schema']['minimum'] = $param['minimum'];
                                    if (isset($param['exclusiveMinimum'])) $param['schema']['exclusiveMinimum'] = $param['exclusiveMinimum'];
                                    if (isset($param['maxLength'])) $param['schema']['maxLength'] = $param['maxLength'];
                                    if (isset($param['minLength'])) $param['schema']['minLength'] = $param['minLength'];
                                    if (isset($param['pattern'])) $param['schema']['pattern'] = $param['pattern'];
                                    if (isset($param['maxItems'])) $param['schema']['maxItems'] = $param['maxItems'];
                                    if (isset($param['minItems'])) $param['schema']['minItems'] = $param['minItems'];
                                    if (isset($param['uniqueItems'])) $param['schema']['uniqueItems'] = $param['uniqueItems'];
                                    if (isset($param['enum'])) $param['schema']['enum'] = $param['enum'];
                                    if (isset($param['multipleOf'])) $param['schema']['multipleOf'] = $param['multipleOf'];
                                    unset($param['type'], $param['format'], $param['items'], $param['default'], $param['maximum'], $param['exclusiveMaximum'], $param['minimum'], $param['exclusiveMinimum'], $param['maxLength'], $param['minLength'], $param['pattern'], $param['maxItems'], $param['minItems'], $param['uniqueItems'], $param['enum'], $param['multipleOf']);
                                }
                            }
                            $operation['parameters'] = array_values($operation['parameters']);
                            if (!empty($formData)) {
                                $content = [];
                                foreach ($opConsumes as $mime) {
                                    if (in_array($mime, ['application/x-www-form-urlencoded', 'multipart/form-data'])) {
                                        $content[$mime] = [
                                            'schema' => [
                                                'type' => 'object',
                                                'properties' => $formData
                                            ]
                                        ];
                                    }
                                }
                                if (empty($content)) {
                                    $content['application/x-www-form-urlencoded'] = [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => $formData
                                        ]
                                    ];
                                }
                                $operation['requestBody'] = [
                                    'content' => $content
                                ];
                            }
                        }
                        if (isset($operation['responses'])) {
                            foreach ($operation['responses'] as $code => &$response) {
                                if (isset($response['schema'])) {
                                    $content = [];
                                    foreach ($opProduces as $mime) {
                                        $content[$mime] = ['schema' => $response['schema']];
                                    }
                                    $response['content'] = empty($content) ? ['application/json' => ['schema' => $response['schema']]] : $content;
                                    unset($response['schema']);
                                }
                                if (isset($response['headers'])) {
                                    foreach ($response['headers'] as $headerName => &$header) {
                                        if (isset($header['type']) && !isset($header['schema'])) {
                                            $header['schema'] = ['type' => $header['type']];
                                            if (isset($header['format'])) $header['schema']['format'] = $header['format'];
                                            if (isset($header['items'])) $header['schema']['items'] = $header['items'];
                                            if (isset($header['default'])) $header['schema']['default'] = $header['default'];
                                            if (isset($header['maximum'])) $header['schema']['maximum'] = $header['maximum'];
                                            if (isset($header['exclusiveMaximum'])) $header['schema']['exclusiveMaximum'] = $header['exclusiveMaximum'];
                                            if (isset($header['minimum'])) $header['schema']['minimum'] = $header['minimum'];
                                            if (isset($header['exclusiveMinimum'])) $header['schema']['exclusiveMinimum'] = $header['exclusiveMinimum'];
                                            if (isset($header['maxLength'])) $header['schema']['maxLength'] = $header['maxLength'];
                                            if (isset($header['minLength'])) $header['schema']['minLength'] = $header['minLength'];
                                            if (isset($header['pattern'])) $header['schema']['pattern'] = $header['pattern'];
                                            if (isset($header['maxItems'])) $header['schema']['maxItems'] = $header['maxItems'];
                                            if (isset($header['minItems'])) $header['schema']['minItems'] = $header['minItems'];
                                            if (isset($header['uniqueItems'])) $header['schema']['uniqueItems'] = $header['uniqueItems'];
                                            if (isset($header['enum'])) $header['schema']['enum'] = $header['enum'];
                                            if (isset($header['multipleOf'])) $header['schema']['multipleOf'] = $header['multipleOf'];
                                            unset($header['type'], $header['format'], $header['items'], $header['default'], $header['maximum'], $header['exclusiveMaximum'], $header['minimum'], $header['exclusiveMinimum'], $header['maxLength'], $header['minLength'], $header['pattern'], $header['maxItems'], $header['minItems'], $header['uniqueItems'], $header['enum'], $header['multipleOf']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        $json = json_encode($data);
        $json = str_replace('#/definitions/', '#/components/schemas/', $json);
        $json = str_replace('#\/definitions\/', '#\/components\/schemas\/', $json);
        $json = str_replace('#/parameters/', '#/components/parameters/', $json);
        $json = str_replace('#\/parameters\/', '#\/components\/parameters\/', $json);
        $json = str_replace('#/responses/', '#/components/responses/', $json);
        $json = str_replace('#\/responses\/', '#\/components\/responses\/', $json);
        $json = str_replace('#/securityDefinitions/', '#/components/securitySchemes/', $json);
        $json = str_replace('#\/securityDefinitions\/', '#\/components\/securitySchemes\/', $json);
        $data = json_decode($json, true);
        if (empty($data['components'])) {
            unset($data['components']);
        }
    }

REPLACEMENT;

array_splice($lines, $start, $end - $start, [$replacement]);
file_put_contents('src/openapi/parse.php', implode("", $lines));
echo "Done replacing.";

