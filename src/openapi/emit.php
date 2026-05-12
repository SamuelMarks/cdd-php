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
function emit(array $openapi, ?string $outDir = null, array $options = []): string {
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
            $clientCode = \Cdd\Client\emit_class($openapi['paths'], file_exists("$srcDir/ApiClient.php") ? file_get_contents("$srcDir/ApiClient.php") : '');
            file_put_contents("$srcDir/ApiClient.php", $clientCode);
        }
        
        if (isset($openapi['components'])) {
            $componentsCode = \Cdd\Components\emit($openapi['components'], file_exists("$srcDir/Models.php") ? file_get_contents("$srcDir/Models.php") : '');
            file_put_contents("$srcDir/Models.php", $componentsCode);
        }

        // Generate Mocks
        if (isset($openapi['components']['examples'])) {
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

        $tests = $options['tests'] ?? false;

        // Generate Tests
        $testCode = "<?php\n\n// Auto-generated tests\n\n";
        if ($tests) {
            $testCode .= "return [\n";
        } else {
            $testCode .= "use PHPUnit\\Framework\\TestCase;\n\nclass ApiTests extends TestCase {\n";
        }
        
        if (isset($openapi['paths'])) {
            foreach ($openapi['paths'] as $path => $methods) {
                foreach ($methods as $method => $operation) {
                    $testCode .= \Cdd\Tests\emit($method, $path, $operation, $tests) . "\n";
                }
            }
        }
        
        if ($tests) {
            $testCode .= "];\n";
        } else {
            $testCode .= "}\n";
        }
        file_put_contents("$srcDir/ApiTests.php", $testCode);
        
        $noInstallablePackage = $options['no_installable_package'] ?? false;
        $noGithubActions = $options['no_github_actions'] ?? false;
        
        if (!$noInstallablePackage) {
            if (!file_exists("$outDir/composer.json")) {
                file_put_contents("$outDir/composer.json", json_encode([
                    "name" => "offscale/generated-api",
                    "description" => "Generated API client/server",
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
                file_put_contents("$outDir/.github/workflows/ci.yml", "name: CI\non: [push]\njobs:\n  test:\n    runs-on: ubuntu-latest\n    steps:\n    - uses: actions/checkout@v3\n    - name: Use PHP\n      uses: shivammathur/setup-php@v2\n      with:\n        php-version: '8.2'\n    - run: composer install\n    - run: composer test\n");
            }
        }
    }

    $json = json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new \RuntimeException('Failed to encode OpenAPI array to JSON: ' . json_last_error_msg());
    }
    
    return $json;
}
