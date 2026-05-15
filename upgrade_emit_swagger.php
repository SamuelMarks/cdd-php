<?php

$lines = file('src/openapi/emit.php');
$start = -1;
$end = -1;
foreach ($lines as $i => $line) {
    if (strpos($line, '$json = json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);') !== false) {
        $start = $i;
        break;
    }
}

$replacement = <<<'REPLACEMENT'
    $targetVersion = $options['target_version'] ?? '3.2.0';
    if ($targetVersion === '2.0') {
        $swagger = ['swagger' => '2.0'];
        if (isset($openapi['info'])) $swagger['info'] = $openapi['info'];
        if (isset($openapi['servers']) && count($openapi['servers']) > 0) {
            $url = parse_url($openapi['servers'][0]['url']);
            if (isset($url['host'])) $swagger['host'] = $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
            if (isset($url['path'])) $swagger['basePath'] = $url['path'];
            if (isset($url['scheme'])) $swagger['schemes'] = [$url['scheme']];
        }
        $swagger['consumes'] = ['application/json'];
        $swagger['produces'] = ['application/json'];
        if (isset($openapi['paths'])) {
            $swagger['paths'] = (array)$openapi['paths'];
            foreach ($swagger['paths'] as $path => &$pathItem) {
                foreach ($pathItem as $method => &$op) {
                    if (in_array(strtolower($method), ['get', 'put', 'post', 'delete', 'options', 'head', 'patch'])) {
                        if (isset($op['requestBody'])) {
                            $content = $op['requestBody']['content'] ?? [];
                            foreach ($content as $mime => $media) {
                                if ($mime === 'application/x-www-form-urlencoded' || $mime === 'multipart/form-data') {
                                    $props = $media['schema']['properties'] ?? [];
                                    if (!isset($op['parameters'])) $op['parameters'] = [];
                                    foreach ($props as $name => $propSchema) {
                                        $p = $propSchema;
                                        $p['name'] = $name;
                                        $p['in'] = 'formData';
                                        $op['parameters'][] = $p;
                                    }
                                } else {
                                    $p = [
                                        'in' => 'body',
                                        'name' => 'body',
                                        'required' => $op['requestBody']['required'] ?? false,
                                        'schema' => $media['schema'] ?? []
                                    ];
                                    if (isset($op['requestBody']['description'])) $p['description'] = $op['requestBody']['description'];
                                    if (!isset($op['parameters'])) $op['parameters'] = [];
                                    $op['parameters'][] = $p;
                                }
                                break; // Only take first content type
                            }
                            unset($op['requestBody']);
                        }
                        if (isset($op['responses'])) {
                            foreach ($op['responses'] as $code => &$resp) {
                                if (isset($resp['content'])) {
                                    foreach ($resp['content'] as $mime => $media) {
                                        $resp['schema'] = $media['schema'] ?? [];
                                        break; // Only take first content type
                                    }
                                    unset($resp['content']);
                                }
                            }
                        }
                    }
                }
            }
        }
        if (isset($openapi['components']['schemas'])) {
            $swagger['definitions'] = $openapi['components']['schemas'];
        }
        if (isset($openapi['components']['parameters'])) {
            $swagger['parameters'] = $openapi['components']['parameters'];
        }
        if (isset($openapi['components']['responses'])) {
            $swagger['responses'] = $openapi['components']['responses'];
        }
        if (isset($openapi['components']['securitySchemes'])) {
            $swagger['securityDefinitions'] = $openapi['components']['securitySchemes'];
            foreach ($swagger['securityDefinitions'] as $name => &$scheme) {
                if ($scheme['type'] === 'http' && isset($scheme['scheme']) && $scheme['scheme'] === 'basic') {
                    $scheme['type'] = 'basic';
                    unset($scheme['scheme']);
                }
                if (isset($scheme['flows'])) {
                    $scheme['type'] = 'oauth2';
                    foreach ($scheme['flows'] as $flowType => $flow) {
                        $ft = $flowType;
                        if ($ft === 'clientCredentials') $ft = 'application';
                        if ($ft === 'authorizationCode') $ft = 'accessCode';
                        $scheme['flow'] = $ft;
                        if (isset($flow['authorizationUrl'])) $scheme['authorizationUrl'] = $flow['authorizationUrl'];
                        if (isset($flow['tokenUrl'])) $scheme['tokenUrl'] = $flow['tokenUrl'];
                        if (isset($flow['scopes'])) $scheme['scopes'] = $flow['scopes'];
                        break;
                    }
                    unset($scheme['flows']);
                }
            }
        }
        if (isset($openapi['security'])) $swagger['security'] = $openapi['security'];
        if (isset($openapi['tags'])) $swagger['tags'] = $openapi['tags'];
        if (isset($openapi['externalDocs'])) $swagger['externalDocs'] = $openapi['externalDocs'];
        
        $jsonStr = json_encode($swagger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $jsonStr = str_replace('#/components/schemas/', '#/definitions/', $jsonStr);
        $jsonStr = str_replace('#\/components\/schemas\/', '#\/definitions\/', $jsonStr);
        $jsonStr = str_replace('#/components/parameters/', '#/parameters/', $jsonStr);
        $jsonStr = str_replace('#\/components\/parameters\/', '#\/parameters\/', $jsonStr);
        $jsonStr = str_replace('#/components/responses/', '#/responses/', $jsonStr);
        $jsonStr = str_replace('#\/components\/responses\/', '#\/responses\/', $jsonStr);
        $jsonStr = str_replace('#/components/securitySchemes/', '#/securityDefinitions/', $jsonStr);
        $jsonStr = str_replace('#\/components\/securitySchemes\/', '#\/securityDefinitions\/', $jsonStr);
        $openapiToEncode = json_decode($jsonStr, true);
    } else {
        $openapiToEncode = $openapi;
    }

    $json = json_encode($openapiToEncode, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
REPLACEMENT;

array_splice($lines, $start, 1, [$replacement]);
file_put_contents('src/openapi/emit.php', implode("", $lines));
echo "Done replacing emit.";

