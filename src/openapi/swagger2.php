<?php

/**
 * Converts between Swagger 2.0 and OpenAPI 3.2.0 specification structures.
 *
 * @package Cdd\Openapi
 */

declare(strict_types=1);

namespace Cdd\Openapi;

/**
 * Extracts Swagger 2.0 inline parameter/header schema validation keywords into an OpenAPI 3.x `schema` sub-object
 * and maps `collectionFormat` to OpenAPI 3.x `style`, `explode`, and `x-collectionFormat`.
 *
 * @param array<string, mixed> $item The Swagger 2.0 parameter or header array.
 * @param bool $isHeader Whether the item is a Response Header Object.
 * @return array<string, mixed> The updated item with a nested `schema` array.
 */
function upgrade_swagger2_inline_schema(array $item, bool $isHeader = false): array
{
    $schemaKeys = [
        'format',
        'items',
        'default',
        'maximum',
        'exclusiveMaximum',
        'minimum',
        'exclusiveMinimum',
        'maxLength',
        'minLength',
        'pattern',
        'maxItems',
        'minItems',
        'uniqueItems',
        'enum',
        'multipleOf',
    ];
    $item['schema'] = ['type' => $item['type']];
    unset($item['type']);
    foreach ($schemaKeys as $key) {
        if (isset($item[$key])) {
            $item['schema'][$key] = $item[$key];
            unset($item[$key]);
        }
    }
    if (isset($item['collectionFormat']) && is_string($item['collectionFormat'])) {
        $cf = $item['collectionFormat'];
        unset($item['collectionFormat']);
        $item['x-collectionFormat'] = $cf;
        if ($isHeader || !isset($item['in'])) {
            $item['style'] = 'simple';
            $item['explode'] = false;
        } else {
            $in = (string) $item['in'];
            $isPathOrHeader = ($in === 'path' || $in === 'header');
            if ($cf === 'multi') {
                $item['style'] = 'form';
                $item['explode'] = true;
            } elseif ($cf === 'ssv') {
                $item['style'] = $isPathOrHeader ? 'simple' : 'spaceDelimited';
                $item['explode'] = false;
            } elseif ($cf === 'pipes' || $cf === 'tsv') {
                $item['style'] = $isPathOrHeader ? 'simple' : 'pipeDelimited';
                $item['explode'] = false;
            } else {
                $item['style'] = $isPathOrHeader ? 'simple' : 'form';
                $item['explode'] = false;
            }
        }
    }
    return $item;
}

/**
 * Flattens an OpenAPI 3.x parameter or header `schema` sub-object and `style`/`explode`/`x-collectionFormat`
 * back into a Swagger 2.0 inline parameter or header array.
 *
 * @param array<string, mixed> $item The OpenAPI 3.x parameter or header array.
 * @param bool $isHeader Whether the item is a Response Header Object.
 * @return array<string, mixed> The downgraded Swagger 2.0 parameter or header array.
 */
function downgrade_openapi3_inline_schema(array $item, bool $isHeader = false): array
{
    if (isset($item['schema']) && is_array($item['schema'])) {
        foreach ($item['schema'] as $k => $v) {
            $item[$k] = $v;
        }
        unset($item['schema']);
    }
    if (isset($item['x-collectionFormat']) && is_string($item['x-collectionFormat'])) {
        $item['collectionFormat'] = $item['x-collectionFormat'];
        unset($item['x-collectionFormat'], $item['style'], $item['explode']);
    } elseif (isset($item['style']) || isset($item['explode'])) {
        $style = (string) ($item['style'] ?? '');
        $explode = $item['explode'] ?? null;
        if (!$isHeader && $explode === true && ($style === 'form' || $style === '')) {
            $item['collectionFormat'] = 'multi';
        } elseif ($style === 'spaceDelimited') {
            $item['collectionFormat'] = 'ssv';
        } elseif ($style === 'pipeDelimited') {
            $item['collectionFormat'] = 'pipes';
        } elseif (($item['type'] ?? '') === 'array') {
            $item['collectionFormat'] = 'csv';
        }
        unset($item['style'], $item['explode']);
    }
    return $item;
}

/**
 * Upgrades a parsed Swagger 2.0 array structure into an OpenAPI 3.x structure.
 *
 * @param array<string, mixed> $data The parsed Swagger 2.0 array.
 * @return array<string, mixed> The upgraded OpenAPI 3.x array.
 */
function upgrade_swagger2_to_openapi3(array $data): array
{
    $data['openapi'] = '3.0.0';

    $rootHost = (string) ($data['host'] ?? 'localhost');
    $rootBasePath = (string) ($data['basePath'] ?? '/');
    if (isset($data['host']) || isset($data['basePath']) || isset($data['schemes'])) {
        $schemes = $data['schemes'] ?? ['http'];
        $servers = [];
        foreach ($schemes as $scheme) {
            $servers[] = ['url' => $scheme . '://' . $rootHost . $rootBasePath];
        }
        $data['servers'] = $servers;
        unset($data['host'], $data['basePath'], $data['schemes']);
    }

    $globalConsumes = $data['consumes'] ?? ['application/json'];
    $globalProduces = $data['produces'] ?? ['application/json'];
    unset($data['consumes'], $data['produces']);

    if (!isset($data['components'])) {
        $data['components'] = [];
    }
    if (isset($data['definitions'])) {
        $data['components']['schemas'] = $data['definitions'];
        unset($data['definitions']);
    }
    if (isset($data['parameters']) && is_array($data['parameters'])) {
        foreach ($data['parameters'] as &$compParam) {
            if (is_array($compParam) && !isset($compParam['schema']) && isset($compParam['type'])) {
                $compParam = upgrade_swagger2_inline_schema($compParam, false);
            }
        }
        $data['components']['parameters'] = $data['parameters'];
        unset($data['parameters']);
    }
    if (isset($data['responses']) && is_array($data['responses'])) {
        foreach ($data['responses'] as &$compResp) {
            if (is_array($compResp)) {
                if (isset($compResp['schema'])) {
                    $content = [];
                    $producesList = !empty($globalProduces) ? $globalProduces : ['application/json'];
                    foreach ($producesList as $mime) {
                        $content[$mime] = ['schema' => $compResp['schema']];
                    }
                    $compResp['content'] = $content;
                    unset($compResp['schema']);
                }
                if (isset($compResp['examples']) && is_array($compResp['examples'])) {
                    foreach ($compResp['examples'] as $exMime => $exVal) {
                        $compResp['content'][(string) $exMime]['example'] = $exVal;
                    }
                    unset($compResp['examples']);
                }
                if (isset($compResp['headers']) && is_array($compResp['headers'])) {
                    foreach ($compResp['headers'] as &$compHeader) {
                        if (is_array($compHeader) && isset($compHeader['type']) && !isset($compHeader['schema'])) {
                            $compHeader = upgrade_swagger2_inline_schema($compHeader, true);
                        }
                    }
                }
            }
        }
        $data['components']['responses'] = $data['responses'];
        unset($data['responses']);
    }
    if (isset($data['securityDefinitions']) && is_array($data['securityDefinitions'])) {
        foreach ($data['securityDefinitions'] as &$scheme) {
            if ($scheme['type'] === 'basic') {
                $scheme['type'] = 'http';
                $scheme['scheme'] = 'basic';
            }
            if ($scheme['type'] === 'oauth2') {
                $flowType = $scheme['flow'] ?? 'implicit';
                if ($flowType === 'application') {
                    $flowType = 'clientCredentials';
                }
                if ($flowType === 'accessCode') {
                    $flowType = 'authorizationCode';
                }
                $flow = [];
                if (isset($scheme['authorizationUrl'])) {
                    $flow['authorizationUrl'] = $scheme['authorizationUrl'];
                }
                if (isset($scheme['tokenUrl'])) {
                    $flow['tokenUrl'] = $scheme['tokenUrl'];
                }
                if (isset($scheme['scopes'])) {
                    $flow['scopes'] = $scheme['scopes'];
                }
                $scheme['flows'] = [$flowType => $flow];
                unset($scheme['flow'], $scheme['authorizationUrl'], $scheme['tokenUrl'], $scheme['scopes']);
            }
        }
        $data['components']['securitySchemes'] = $data['securityDefinitions'];
        unset($data['securityDefinitions']);
    }

    $validMethods = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'];

    if (isset($data['paths']) && is_array($data['paths'])) {
        foreach ($data['paths'] as &$pathItem) {
            if (isset($pathItem['parameters']) && is_array($pathItem['parameters'])) {
                foreach ($pathItem['parameters'] as &$pathParam) {
                    if (is_array($pathParam) && !isset($pathParam['schema']) && isset($pathParam['type'])) {
                        $pathParam = upgrade_swagger2_inline_schema($pathParam, false);
                    }
                }
            }
            foreach ($pathItem as $method => &$operation) {
                if (in_array(strtolower((string) $method), $validMethods, true)) {
                    $opConsumes = $operation['consumes'] ?? $globalConsumes;
                    $opProduces = $operation['produces'] ?? $globalProduces;
                    unset($operation['consumes'], $operation['produces']);

                    if (isset($operation['schemes']) && is_array($operation['schemes'])) {
                        $opServers = [];
                        foreach ($operation['schemes'] as $opScheme) {
                            $opServers[] = ['url' => $opScheme . '://' . $rootHost . $rootBasePath];
                        }
                        $operation['servers'] = $opServers;
                        unset($operation['schemes']);
                    }

                    if (isset($operation['parameters']) && is_array($operation['parameters'])) {
                        $formData = [];
                        $formRequired = [];
                        foreach ($operation['parameters'] as $i => &$param) {
                            if (isset($param['in']) && $param['in'] === 'body') {
                                $schema = $param['schema'] ?? [];
                                $content = [];
                                foreach ($opConsumes as $mime) {
                                    $content[$mime] = ['schema' => $schema];
                                }
                                $operation['requestBody'] = [
                                    'content' => empty($content) ? ['application/json' => ['schema' => $schema]] : $content,
                                    'required' => $param['required'] ?? false,
                                ];
                                if (isset($param['description'])) {
                                    $operation['requestBody']['description'] = $param['description'];
                                }
                                unset($operation['parameters'][$i]);
                            } elseif (isset($param['in']) && $param['in'] === 'formData') {
                                $upgradedForm = upgrade_swagger2_inline_schema($param, false);
                                $schema = $upgradedForm['schema'] ?? ['type' => 'string'];
                                if (isset($upgradedForm['description'])) {
                                    $schema['description'] = $upgradedForm['description'];
                                }
                                if (isset($upgradedForm['allowEmptyValue'])) {
                                    $schema['allowEmptyValue'] = $upgradedForm['allowEmptyValue'];
                                }
                                if (isset($upgradedForm['x-collectionFormat'])) {
                                    $schema['x-collectionFormat'] = $upgradedForm['x-collectionFormat'];
                                }
                                foreach ($upgradedForm as $fk => $fv) {
                                    if (str_starts_with((string) $fk, 'x-') && $fk !== 'x-collectionFormat') {
                                        $schema[$fk] = $fv;
                                    }
                                }
                                if (!empty($param['required'])) {
                                    $formRequired[] = (string) $param['name'];
                                }
                                $formData[$param['name']] = $schema;
                                unset($operation['parameters'][$i]);
                            } elseif (!isset($param['schema']) && isset($param['type'])) {
                                $param = upgrade_swagger2_inline_schema($param, false);
                            }
                        }
                        $operation['parameters'] = array_values($operation['parameters']);
                        if (!empty($formData)) {
                            $formSchema = [
                                'type' => 'object',
                                'properties' => $formData,
                            ];
                            if (!empty($formRequired)) {
                                $formSchema['required'] = $formRequired;
                            }
                            $content = [];
                            foreach ($opConsumes as $mime) {
                                if (in_array($mime, ['application/x-www-form-urlencoded', 'multipart/form-data'], true)) {
                                    $content[$mime] = ['schema' => $formSchema];
                                }
                            }
                            if (empty($content)) {
                                $content['application/x-www-form-urlencoded'] = ['schema' => $formSchema];
                            }
                            $operation['requestBody'] = [
                                'content' => $content,
                            ];
                        }
                    }
                    if (isset($operation['responses']) && is_array($operation['responses'])) {
                        foreach ($operation['responses'] as &$response) {
                            if (isset($response['schema'])) {
                                $content = [];
                                foreach ($opProduces as $mime) {
                                    $content[$mime] = ['schema' => $response['schema']];
                                }
                                $response['content'] = empty($content) ? ['application/json' => ['schema' => $response['schema']]] : $content;
                                unset($response['schema']);
                            }
                            if (isset($response['examples']) && is_array($response['examples'])) {
                                foreach ($response['examples'] as $exMime => $exVal) {
                                    $response['content'][(string) $exMime]['example'] = $exVal;
                                }
                                unset($response['examples']);
                            }
                            if (isset($response['headers']) && is_array($response['headers'])) {
                                foreach ($response['headers'] as &$header) {
                                    if (isset($header['type']) && !isset($header['schema'])) {
                                        $header = upgrade_swagger2_inline_schema($header, true);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $data = rewrite_ref_prefixes($data, [
        '#/definitions/' => '#/components/schemas/',
        '#/parameters/' => '#/components/parameters/',
        '#/responses/' => '#/components/responses/',
        '#/securityDefinitions/' => '#/components/securitySchemes/',
    ]);

    if (empty($data['components'])) {
        unset($data['components']);
    }

    return $data;
}

/**
 * Downgrades an OpenAPI 3.x structure into a Swagger 2.0 specification array.
 *
 * @param array<string, mixed> $openapi The OpenAPI 3.x specification array.
 * @return array<string, mixed> The converted Swagger 2.0 specification array.
 */
function downgradeOpenApi3ToSwagger2(array $openapi): array
{
    $swagger = ['swagger' => '2.0'];
    if (isset($openapi['info'])) {
        $swagger['info'] = $openapi['info'];
    }
    if (isset($openapi['servers']) && count($openapi['servers']) > 0) {
        $url = parse_url((string) $openapi['servers'][0]['url']);
        if (isset($url['host'])) {
            $swagger['host'] = $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
        }
        if (isset($url['path'])) {
            $swagger['basePath'] = $url['path'];
        }
        $schemes = [];
        foreach ($openapi['servers'] as $srv) {
            $srvUrl = parse_url((string) ($srv['url'] ?? ''));
            if (isset($srvUrl['scheme']) && !in_array($srvUrl['scheme'], $schemes, true)) {
                $schemes[] = $srvUrl['scheme'];
            }
        }
        if (!empty($schemes)) {
            $swagger['schemes'] = $schemes;
        }
    }
    $swagger['consumes'] = ['application/json'];
    $swagger['produces'] = ['application/json'];
    if (isset($openapi['paths'])) {
        $swagger['paths'] = (array) $openapi['paths'];
        foreach ($swagger['paths'] as $path => &$pathItem) {
            if (isset($pathItem['parameters']) && is_array($pathItem['parameters'])) {
                foreach ($pathItem['parameters'] as &$pathParam) {
                    if (is_array($pathParam) && ($pathParam['in'] ?? '') !== 'body') {
                        $pathParam = downgrade_openapi3_inline_schema($pathParam, false);
                    }
                }
            }
            foreach ($pathItem as $method => &$op) {
                if (in_array(strtolower((string) $method), ['get', 'put', 'post', 'delete', 'options', 'head', 'patch'], true)) {
                    if (isset($op['servers']) && is_array($op['servers'])) {
                        $opSchemes = [];
                        foreach ($op['servers'] as $opSrv) {
                            $opUrl = parse_url((string) ($opSrv['url'] ?? ''));
                            if (isset($opUrl['scheme']) && !in_array($opUrl['scheme'], $opSchemes, true)) {
                                $opSchemes[] = $opUrl['scheme'];
                            }
                        }
                        if (!empty($opSchemes)) {
                            $op['schemes'] = $opSchemes;
                        }
                        unset($op['servers']);
                    }
                    if (isset($op['parameters']) && is_array($op['parameters'])) {
                        foreach ($op['parameters'] as &$existingParam) {
                            if (is_array($existingParam) && ($existingParam['in'] ?? '') !== 'body') {
                                $existingParam = downgrade_openapi3_inline_schema($existingParam, false);
                            }
                        }
                    }
                    if (isset($op['requestBody'])) {
                        $content = $op['requestBody']['content'] ?? [];
                        $contentMimes = array_keys($content);
                        if (count($contentMimes) > 1 || (count($contentMimes) === 1 && $contentMimes[0] !== 'application/json')) {
                            $op['consumes'] = $contentMimes;
                        }
                        foreach ($content as $mime => $media) {
                            if ($mime === 'application/x-www-form-urlencoded' || $mime === 'multipart/form-data') {
                                $props = $media['schema']['properties'] ?? [];
                                $reqFields = is_array($media['schema']['required'] ?? null) ? $media['schema']['required'] : [];
                                if (!isset($op['parameters'])) {
                                    $op['parameters'] = [];
                                }
                                foreach ($props as $name => $propSchema) {
                                    $p = $propSchema;
                                    if (isset($p['x-collectionFormat'])) {
                                        $p['collectionFormat'] = $p['x-collectionFormat'];
                                        unset($p['x-collectionFormat']);
                                    }
                                    $p['name'] = $name;
                                    $p['in'] = 'formData';
                                    if (in_array($name, $reqFields, true)) {
                                        $p['required'] = true;
                                    }
                                    $op['parameters'][] = $p;
                                }
                            } else {
                                $p = [
                                    'in' => 'body',
                                    'name' => 'body',
                                    'required' => $op['requestBody']['required'] ?? false,
                                    'schema' => $media['schema'] ?? [],
                                ];
                                if (isset($op['requestBody']['description'])) {
                                    $p['description'] = $op['requestBody']['description'];
                                }
                                if (!isset($op['parameters'])) {
                                    $op['parameters'] = [];
                                }
                                $op['parameters'][] = $p;
                            }
                            break;
                        }
                        unset($op['requestBody']);
                    }
                    if (isset($op['responses'])) {
                        $opProduces = [];
                        foreach ($op['responses'] as $code => &$resp) {
                            if (isset($resp['content']) && is_array($resp['content'])) {
                                $examples = [];
                                foreach ($resp['content'] as $mime => $media) {
                                    if (!in_array((string) $mime, $opProduces, true)) {
                                        $opProduces[] = (string) $mime;
                                    }
                                    if (!isset($resp['schema']) && isset($media['schema'])) {
                                        $resp['schema'] = $media['schema'];
                                    }
                                    if (array_key_exists('example', $media)) {
                                        $examples[(string) $mime] = $media['example'];
                                    }
                                }
                                if (!empty($examples)) {
                                    $resp['examples'] = $examples;
                                }
                                unset($resp['content']);
                            }
                            if (isset($resp['headers']) && is_array($resp['headers'])) {
                                foreach ($resp['headers'] as &$respHeader) {
                                    if (is_array($respHeader)) {
                                        $respHeader = downgrade_openapi3_inline_schema($respHeader, true);
                                    }
                                }
                            }
                        }
                        if (count($opProduces) > 1 || (count($opProduces) === 1 && $opProduces[0] !== 'application/json')) {
                            $op['produces'] = $opProduces;
                        }
                    }
                }
            }
        }
    }
    if (isset($openapi['components']['schemas'])) {
        $swagger['definitions'] = $openapi['components']['schemas'];
    }
    if (isset($openapi['components']['parameters']) && is_array($openapi['components']['parameters'])) {
        $swagger['parameters'] = $openapi['components']['parameters'];
        foreach ($swagger['parameters'] as &$compParam) {
            if (is_array($compParam) && ($compParam['in'] ?? '') !== 'body') {
                $compParam = downgrade_openapi3_inline_schema($compParam, false);
            }
        }
    }
    if (isset($openapi['components']['responses']) && is_array($openapi['components']['responses'])) {
        $swagger['responses'] = $openapi['components']['responses'];
        foreach ($swagger['responses'] as &$compResp) {
            if (is_array($compResp)) {
                if (isset($compResp['content']) && is_array($compResp['content'])) {
                    $examples = [];
                    foreach ($compResp['content'] as $mime => $media) {
                        if (!isset($compResp['schema']) && isset($media['schema'])) {
                            $compResp['schema'] = $media['schema'];
                        }
                        if (array_key_exists('example', $media)) {
                            $examples[(string) $mime] = $media['example'];
                        }
                    }
                    if (!empty($examples)) {
                        $compResp['examples'] = $examples;
                    }
                    unset($compResp['content']);
                }
                if (isset($compResp['headers']) && is_array($compResp['headers'])) {
                    foreach ($compResp['headers'] as &$compHeader) {
                        if (is_array($compHeader)) {
                            $compHeader = downgrade_openapi3_inline_schema($compHeader, true);
                        }
                    }
                }
            }
        }
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
                    if ($ft === 'clientCredentials') {
                        $ft = 'application';
                    }
                    if ($ft === 'authorizationCode') {
                        $ft = 'accessCode';
                    }
                    $scheme['flow'] = $ft;
                    if (isset($flow['authorizationUrl'])) {
                        $scheme['authorizationUrl'] = $flow['authorizationUrl'];
                    }
                    if (isset($flow['tokenUrl'])) {
                        $scheme['tokenUrl'] = $flow['tokenUrl'];
                    }
                    if (isset($flow['scopes'])) {
                        $scheme['scopes'] = $flow['scopes'];
                    }
                    break;
                }
                unset($scheme['flows']);
            }
        }
    }
    if (isset($openapi['security'])) {
        $swagger['security'] = $openapi['security'];
    }
    if (isset($openapi['tags'])) {
        $swagger['tags'] = $openapi['tags'];
    }
    if (isset($openapi['externalDocs'])) {
        $swagger['externalDocs'] = $openapi['externalDocs'];
    }
    foreach ($openapi as $rootKey => $rootVal) {
        if (str_starts_with((string) $rootKey, 'x-')) {
            $swagger[$rootKey] = $rootVal;
        }
    }

    return rewrite_ref_prefixes($swagger, [
        '#/components/schemas/' => '#/definitions/',
        '#/components/parameters/' => '#/parameters/',
        '#/components/responses/' => '#/responses/',
        '#/components/securitySchemes/' => '#/securityDefinitions/',
    ]);
}
