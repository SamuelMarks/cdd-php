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
function emit(array $openapi, ?string $outDir = null): string {
    // Ensuring basic fields are present for 3.2.0 compliance
    if (!isset($openapi['openapi'])) {
        $openapi['openapi'] = '3.2.0';
    }
    
    if (!isset($openapi['info'])) {
        $openapi['info'] = [
            'title' => 'Default API',
            'version' => '1.0.0'
        ];
    }
    
    if (!isset($openapi['paths']) && !isset($openapi['components']) && !isset($openapi['webhooks'])) {
        $openapi['paths'] = (object)[]; // Empty paths object
    }

    if ($outDir) {
        if (!is_dir($outDir)) {
            mkdir($outDir, 0777, true);
        }
        
        $serverCode = "<?php\n\nclass ApiServers {\n";
        if (isset($openapi['servers'])) {
            $serverCode .= \Cdd\Servers\emit($openapi['servers']);
        }
        $serverCode .= "}\n";
        file_put_contents("$outDir/ApiServers.php", $serverCode);

        // Emit api_metadata.php for root OpenAPI properties
        $metadata = [];
        foreach (['info', 'jsonSchemaDialect', 'externalDocs', 'tags', 'security'] as $key) {
            if (isset($openapi[$key])) {
                $metadata[$key] = $openapi[$key];
            }
        }
        if (!empty($metadata)) {
            $metadataCode = "<?php\n\n// Auto-generated API metadata\n\nreturn " . var_export($metadata, true) . ";\n";
            file_put_contents("$outDir/api_metadata.php", $metadataCode);
        }
        
        if (isset($openapi['paths'])) {
            $controllerCode = \Cdd\Paths\emit($openapi['paths'], file_exists("$outDir/ApiController.php") ? file_get_contents("$outDir/ApiController.php") : '');
            file_put_contents("$outDir/ApiController.php", $controllerCode);
            
            $routeCode = \Cdd\Routes\emit($openapi['paths'], file_exists("$outDir/routes.php") ? file_get_contents("$outDir/routes.php") : '');
            file_put_contents("$outDir/routes.php", $routeCode);
            
            // Client generation
            $clientCode = \Cdd\Client\emit_class($openapi['paths'], file_exists("$outDir/ApiClient.php") ? file_get_contents("$outDir/ApiClient.php") : '');
            file_put_contents("$outDir/ApiClient.php", $clientCode);
        }
        
        if (isset($openapi['components'])) {
            $componentsCode = \Cdd\Components\emit($openapi['components'], file_exists("$outDir/Models.php") ? file_get_contents("$outDir/Models.php") : '');
            file_put_contents("$outDir/Models.php", $componentsCode);
        }

        // Generate Mocks
        if (isset($openapi['components']['examples'])) {
            $mocksCode = \Cdd\Mocks\emit($openapi['components']['examples'], file_exists("$outDir/mocks.php") ? file_get_contents("$outDir/mocks.php") : '');
            file_put_contents("$outDir/mocks.php", $mocksCode);
        }

        // Generate Webhooks
        if (isset($openapi['webhooks']) && function_exists('\Cdd\Webhooks\emit')) {
            $webhooksCode = \Cdd\Webhooks\emit($openapi['webhooks'], file_exists("$outDir/Webhooks.php") ? file_get_contents("$outDir/Webhooks.php") : '');
            if ($webhooksCode) {
                file_put_contents("$outDir/Webhooks.php", $webhooksCode);
            }
        }

        // Generate Tests
        $testCode = "<?php\n\n// Auto-generated tests\n\n";
        if (isset($openapi['paths'])) {
            foreach ($openapi['paths'] as $path => $methods) {
                foreach ($methods as $method => $operation) {
                    $testCode .= \Cdd\Tests\emit($method, $path, $operation) . "\n";
                }
            }
        }
        file_put_contents("$outDir/ApiTests.php", $testCode);
    }

    $json = json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new \RuntimeException('Failed to encode OpenAPI array to JSON: ' . json_last_error_msg());
    }
    
    return $json;
}
