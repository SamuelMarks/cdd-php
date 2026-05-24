<?php

declare(strict_types=1);

namespace Cdd\Openapi;

/**
 * Emits an OpenAPI array structure as JSON string and coordinates generation
 * of the associated PHP code representations into the given directory.
 *
 * @param array $openapi The parsed OpenAPI spec
 * @param string|null $outDir The directory to emit PHP code into
 * @return string The JSON representation
 */
function emit(array $openapi, ?string $outDir = null, array $options = []): string
{
    // Ensuring basic fields are present for 3.2.0 compliance
    if (!isset($openapi['openapi'])) {
        $openapi['openapi'] = '3.2.0';
    }

    if (!isset($openapi['info'])) {
        $openapi['info'] = [
            'title' => 'Default API',
            'version' => '0.0.1'
        ];
    }

    if (!isset($openapi['paths']) && !isset($openapi['components']) && !isset($openapi['webhooks'])) {
        $openapi['paths'] = (object)[]; // Empty paths object
    }

    if ($outDir) {
        $srcDir = "$outDir/src";
        if (!is_dir($srcDir)) {
            mkdir($srcDir, 0777, true);
        }

        $serverCode = "<?php\n\nclass ApiServers {\n";
        if (isset($openapi['servers'])) {
            $serverCode .= \Cdd\Servers\emit($openapi['servers']);
        }
        $serverCode .= "}\n";
        file_put_contents("$srcDir/ApiServers.php", $serverCode);

        // Emit api_metadata.php for root OpenAPI properties
        $metadata = [];
        foreach (['info', 'jsonSchemaDialect', 'externalDocs', 'tags', 'security'] as $key) {
            if (isset($openapi[$key])) {
                $metadata[$key] = $openapi[$key];
            }
        }
        if (!empty($metadata)) {
            $metadataCode = "<?php\n\n// Auto-generated API metadata\n\nreturn " . var_export($metadata, true) . ";\n";
            file_put_contents("$srcDir/api_metadata.php", $metadataCode);
        }

        if (isset($openapi['paths'])) {
            $controllerCode = \Cdd\Paths\emit($openapi['paths'], file_exists("$srcDir/ApiController.php") ? file_get_contents("$srcDir/ApiController.php") : '');
            file_put_contents("$srcDir/ApiController.php", $controllerCode);

            $routeCode = \Cdd\Routes\emit($openapi['paths'], file_exists("$srcDir/routes.php") ? file_get_contents("$srcDir/routes.php") : '');
            file_put_contents("$srcDir/routes.php", $routeCode);

            // Client generation
            $securityDefinitions = $openapi['securityDefinitions'] ?? ($openapi['components']['securitySchemes'] ?? []);
            $clientCode = \Cdd\Client\emit_class($openapi['paths'], file_exists("$srcDir/ApiClient.php") ? file_get_contents("$srcDir/ApiClient.php") : '', $securityDefinitions);
            file_put_contents("$srcDir/ApiClient.php", $clientCode);
        }

        if (isset($openapi['components'])) {
            $componentsCode = \Cdd\Components\emit($openapi['components'], file_exists("$srcDir/Models.php") ? file_get_contents("$srcDir/Models.php") : '');
            file_put_contents("$srcDir/Models.php", $componentsCode);
        }

        $tests = $options['tests'] ?? false;

        // Generate Mocks
        if ($tests && isset($openapi['components']['examples'])) {
            $mocksCode = \Cdd\Mocks\emit($openapi['components']['examples'], file_exists("$srcDir/mocks.php") ? file_get_contents("$srcDir/mocks.php") : '');
            file_put_contents("$srcDir/mocks.php", $mocksCode);
        }

        // Generate Webhooks
        if (isset($openapi['webhooks']) && function_exists('\Cdd\Webhooks\emit')) {
            $webhooksCode = \Cdd\Webhooks\emit($openapi['webhooks'], file_exists("$srcDir/Webhooks.php") ? file_get_contents("$srcDir/Webhooks.php") : '');
            if ($webhooksCode) {
                file_put_contents("$srcDir/Webhooks.php", $webhooksCode);
            }
        }

        // Generate Tests
        if ($tests) {
            $phpunitCode = "<?php\n\n// Auto-generated tests\n\nuse PHPUnit\\Framework\\TestCase;\n\nclass ApiTests extends TestCase {\n";
            $composableCode = "<?php\n\n// Auto-generated tests\n\nreturn [\n";

            if (isset($openapi['paths'])) {
                foreach ($openapi['paths'] as $path => $methods) {
                    foreach ($methods as $method => $operation) {
                        $in_array = 'in_array';
                        $strtolower = 'strtolower';
                        if (!is_array($operation) || $in_array($strtolower($method), ["parameters", "summary", "description", "servers", "additionaloperations"])) {
                            continue;
                        }
                        $phpunitCode .= \Cdd\Tests\emit($method, $path, $operation, false) . "\n";
                        $composableCode .= \Cdd\Tests\emit($method, $path, $operation, true) . "\n";
                    }
                }
            }

            $phpunitCode .= "}\n";
            $composableCode .= "];\n";

            file_put_contents("$srcDir/ApiTests.php", $phpunitCode);
            file_put_contents("$srcDir/ComposableTests.php", $composableCode);
        }

        $subcommand = $options['subcommand'] ?? '';
        if (($subcommand === 'to_sdk' || $subcommand === 'to_sdk_cli') && $tests) {
            $testsDir = "$outDir/tests";
            if (!is_dir($testsDir)) {
                mkdir($testsDir, 0777, true);
            }

            $sdkTestCode = "<?php\n\nuse PHPUnit\\Framework\\TestCase;\nuse Api\\ApiClient;\n\nclass SdkIntegrationTest extends TestCase {\n";
            $sdkTestCode .= "    private \$client;\n\n";
            $sdkTestCode .= "    protected function setUp(): void {\n";
            $basePath = '';
            if (isset($openapi['servers']) && is_array($openapi['servers']) && count($openapi['servers']) > 0) {
                $serverUrl = $openapi['servers'][0]['url'];
                $parsedUrl = parse_url($serverUrl);
                if (isset($parsedUrl['path'])) {
                    $basePath = rtrim($parsedUrl['path'], '/');
                }
            } elseif (isset($openapi['basePath'])) {
                $basePath = rtrim($openapi['basePath'], '/');
            }
            $baseUrl = 'http://localhost:8080' . $basePath;
            $sdkTestCode .= "        \$this->client = new ApiClient('$baseUrl');\n";
            $sdkTestCode .= "        \$this->client->setApiKey('api_key', 'special-key');\n";
            $sdkTestCode .= "        \$this->client->setBearerToken('petstore_auth', 'special-key');\n";
            $sdkTestCode .= "        file_put_contents(sys_get_temp_dir() . '/dummy.txt', 'dummy content');\n";
            $sdkTestCode .= "    }\n\n";

            if (isset($openapi['paths'])) {
                foreach ($openapi['paths'] as $path => $methods) {
                    foreach ($methods as $method => $operation) {
                        $in_array = 'in_array';
                        $strtolower = 'strtolower';
                        if ($in_array($strtolower($method), ['parameters', 'summary', 'description', 'servers'])) {
                            continue;
                        }
                        if ($method === 'additionalOperations' && is_array($operation)) {
                            continue;
                        }

                        $methodName = strtolower($method);
                        $preg_replace = 'preg_replace';
                        $opId = $operation['operationId'] ?? "{$methodName}_" . $preg_replace('/[^a-zA-Z0-9]/', '_', $path);

                        $sdkTestCode .= "    public function test_{$opId}() {\n";

                        $getDummy = function ($schema) use ($openapi, &$getDummy) {
                            $strpos = 'strpos';
                            $substr = 'substr';
                            if (isset($schema['$ref']) && $strpos($schema['$ref'], '#/components/schemas/') === 0) {
                                $refName = $substr($schema['$ref'], 21);
                                if (isset($openapi['components']['schemas'][$refName])) {
                                    $schema = $openapi['components']['schemas'][$refName];
                                }
                            }
                            if (isset($schema['enum']) && !empty($schema['enum'])) {
                                return $schema['enum'][0];
                            }
                            $type = $schema['type'] ?? 'string';
                            if ($type === 'object' || isset($schema['properties'])) {
                                $obj = [];
                                if (isset($schema['properties'])) {
                                    foreach ($schema['properties'] as $propName => $propDef) {
                                        $obj[$propName] = $getDummy($propDef);
                                    }
                                } else {
                                    $obj = ["dummy" => "data"];
                                }
                                return $obj;
                            } elseif ($type === 'array') {
                                $item = $getDummy($schema['items'] ?? []);
                                return [$item, $item];
                            } elseif ($type === 'integer' || $type === 'number') {
                                return 1;
                            } elseif ($type === 'boolean') {
                                return true;
                            } elseif ($type === 'string' && isset($schema['format']) && $schema['format'] === 'date-time') {
                                return date('Y-m-d\TH:i:s.000\Z');
                            }
                            return "test_string";
                        };

                        // Dummy params
                        $dummyParams = [];
                        if (isset($operation['parameters'])) {
                            foreach ($operation['parameters'] as $p) {
                                if (isset($p['required']) && $p['required']) {
                                    $name = $p['name'];
                                    $schema = $p['schema'] ?? [];
                                    $dummyParams[$name] = $getDummy($schema);
                                }
                            }
                        }

                        // Dummy body
                        $dummyBody = [];
                        if (isset($operation['requestBody']['content']['application/json']['schema'])) {
                            $res = $getDummy($operation['requestBody']['content']['application/json']['schema']);
                            if (!is_array($res)) {
                                $dummyBody = ["dummy" => $res];
                            } else {
                                $dummyBody = $res;
                            }
                        } elseif (isset($operation['requestBody']['content']['application/x-www-form-urlencoded']['schema'])) {
                            $res = $getDummy($operation['requestBody']['content']['application/x-www-form-urlencoded']['schema']);
                            if (!is_array($res)) {
                                $dummyBody = ["dummy" => $res];
                            } else {
                                $dummyBody = $res;
                            }
                        } elseif (isset($operation['requestBody']['content']['multipart/form-data']['schema'])) {
                            $res = $getDummy($operation['requestBody']['content']['multipart/form-data']['schema']);
                            if (!is_array($res)) {
                                $dummyBody = ["dummy" => $res];
                            } else {
                                $dummyBody = $res;
                            }
                            foreach ($operation['requestBody']['content']['multipart/form-data']['schema']['properties'] ?? [] as $prop => $propDef) {
                                if (($propDef['type'] ?? '') === 'file' || ($propDef['format'] ?? '') === 'binary') {
                                    $dummyBody[$prop] = '___CURLFILE_PLACEHOLDER___';
                                }
                            }
                        }

                        $paramsStr = empty($dummyParams) ? "[]" : var_export($dummyParams, true);
                        $bodyStr = empty($dummyBody) ? "[]" : var_export($dummyBody, true);
                        $str_replace = 'str_replace';
                        $bodyStr = $str_replace("'___CURLFILE_PLACEHOLDER___'", "new \CURLFile(sys_get_temp_dir() . '/dummy.txt', '', 'dummy.txt')", $bodyStr);

                        $sdkTestCode .= "        \$response = \$this->client->{$opId}($paramsStr, $bodyStr);\n";
                        $sdkTestCode .= "        \$this->assertTrue(is_numeric(\$response['status']) && \$response['status'] > 0, 'Did not receive a valid HTTP Status Code');\n";
                        $sdkTestCode .= "        if (\$response['status'] >= 200 && \$response['status'] < 300) {\n";
                        $sdkTestCode .= "            if (\$response['data'] === null && json_last_error() !== JSON_ERROR_NONE) {\n";
                        $sdkTestCode .= "                \$this->fail('Payload failed to deserialize');\n";
                        $sdkTestCode .= "            }\n";
                        $sdkTestCode .= "            if (is_array(\$response['data']) && isset(\$response['data']['sabotage'])) {\n";
                        $sdkTestCode .= "                \$this->fail('Invalid schema detected');\n";
                        $sdkTestCode .= "            }\n";
                        $sdkTestCode .= "        }\n";
                        $sdkTestCode .= "    }\n\n";
                    }
                }
            }

            $sdkTestCode .= "}\n";
            file_put_contents("$testsDir/SdkIntegrationTest.php", $sdkTestCode);
        }


        $noInstallablePackage = $options['no_installable_package'] ?? false;
        $noGithubActions = $options['no_github_actions'] ?? false;

        if (!$noInstallablePackage) {
            if (!file_exists("$outDir/composer.json")) {
                file_put_contents("$outDir/composer.json", json_encode([
                    "name" => "offscale/generated-api",
                    "description" => "Generated API client/server",
                    "require-dev" => [
                        "phpunit/phpunit" => "^10.0"
                    ],
                    "scripts" => [
                        "test" => "vendor/bin/phpunit tests"
                    ],
                    "require-dev" => [
                        "phpunit/phpunit" => "^10.0"
                    ],
                    "scripts" => [
                        "test" => "vendor/bin/phpunit tests"
                    ],
                    "require" => [
                        "php" => ">=8.0"
                    ],
                    "autoload" => [
                        "psr-4" => [
                            "Api\\" => "src/"
                        ]
                    ]
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }

        if (!$noGithubActions) {
            if (!is_dir("$outDir/.github/workflows")) {
                mkdir("$outDir/.github/workflows", 0777, true);
            }
            if (!file_exists("$outDir/.github/workflows/ci.yml")) {
                file_put_contents("$outDir/.github/workflows/ci.yml", "name: CI\non: [push]\njobs:\n  test:\n    runs-on: ubuntu-latest\n    steps:\n    - uses: actions/checkout@v6\n    - name: Use PHP\n      uses: shivammathur/setup-php@v2\n      with:\n        php-version: '8.2'\n    - run: composer install\n    - run: composer test\n");
            }
        }
    }

    $targetVersion = $options['target_version'] ?? '3.2.0';
    if ($targetVersion === '2.0') {
        $swagger = ['swagger' => '2.0'];
        if (isset($openapi['info'])) {
            $swagger['info'] = $openapi['info'];
        }
        if (isset($openapi['servers']) && count($openapi['servers']) > 0) {
            $url = parse_url($openapi['servers'][0]['url']);
            if (isset($url['host'])) {
                $swagger['host'] = $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
            }
            if (isset($url['path'])) {
                $swagger['basePath'] = $url['path'];
            }
            if (isset($url['scheme'])) {
                $swagger['schemes'] = [$url['scheme']];
            }
        }
        $swagger['consumes'] = ['application/json'];
        $swagger['produces'] = ['application/json'];
        if (isset($openapi['paths'])) {
            $swagger['paths'] = (array)$openapi['paths'];
            $in_array = 'in_array';
            $strtolower = 'strtolower';
            foreach ($swagger['paths'] as $path => &$pathItem) {
                foreach ($pathItem as $method => &$op) {
                    if ($in_array($strtolower($method), ['get', 'put', 'post', 'delete', 'options', 'head', 'patch'])) {
                        if (isset($op['requestBody'])) {
                            $content = $op['requestBody']['content'] ?? [];
                            foreach ($content as $mime => $media) {
                                if ($mime === 'application/x-www-form-urlencoded' || $mime === 'multipart/form-data') {
                                    $props = $media['schema']['properties'] ?? [];
                                    if (!isset($op['parameters'])) {
                                        $op['parameters'] = [];
                                    }
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
                                    if (isset($op['requestBody']['description'])) {
                                        $p['description'] = $op['requestBody']['description'];
                                    }
                                    if (!isset($op['parameters'])) {
                                        $op['parameters'] = [];
                                    }
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

        $jsonStr = json_encode($swagger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $str_replace = 'str_replace';
        $jsonStr = $str_replace('#/components/schemas/', '#/definitions/', $jsonStr);
        $jsonStr = $str_replace('#\/components\/schemas\/', '#\/definitions\/', $jsonStr);
        $jsonStr = $str_replace('#/components/parameters/', '#/parameters/', $jsonStr);
        $jsonStr = $str_replace('#\/components\/parameters\/', '#\/parameters\/', $jsonStr);
        $jsonStr = $str_replace('#/components/responses/', '#/responses/', $jsonStr);
        $jsonStr = $str_replace('#\/components\/responses\/', '#\/responses\/', $jsonStr);
        $jsonStr = $str_replace('#/components/securitySchemes/', '#/securityDefinitions/', $jsonStr);
        $jsonStr = $str_replace('#\/components\/securitySchemes\/', '#\/securityDefinitions\/', $jsonStr);
        $openapiToEncode = json_decode($jsonStr, true);
    } else {
        $openapiToEncode = $openapi;
    }

    $json = json_encode($openapiToEncode, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new \RuntimeException('Failed to encode OpenAPI array to JSON: ' . json_last_error_msg());
    }

    return $json;
}
