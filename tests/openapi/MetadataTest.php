<?php

namespace Cdd\Tests\Openapi;

class MetadataTest extends \Cdd\Tests\Framework\TestCase {
    public function testMetadataEmitAndParse() {
        $tmpDir = sys_get_temp_dir() . '/cdd-php-metadata-' . uniqid();
        mkdir($tmpDir);
        
        $openapi = [
            'openapi' => '3.2.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
                'description' => 'A test api.',
                'termsOfService' => 'http://example.com/tos',
                'contact' => ['name' => 'API Support', 'url' => 'http://example.com/support', 'email' => 'support@example.com'],
                'license' => ['name' => 'Apache 2.0', 'url' => 'http://www.apache.org/licenses/LICENSE-2.0.html']
            ],
            'jsonSchemaDialect' => 'https://json-schema.org/draft/2020-12/schema',
            'externalDocs' => ['url' => 'http://example.com/docs'],
            'security' => [['apiKey' => []]],
            'tags' => [['name' => 'user']],
            'paths' => []
        ];

        \Cdd\Openapi\emit($openapi, $tmpDir);

        $this->assertTrue(file_exists("$tmpDir/api_metadata.php"));

        $baseDir = dirname(__DIR__, 2);
        exec("php $baseDir/bin/cdd-php sync -d $tmpDir", $output, $returnVar);
        
        $json = file_get_contents("$tmpDir/openapi.json");
        $parsed = json_decode($json, true);

        $this->assertEquals('https://json-schema.org/draft/2020-12/schema', $parsed['jsonSchemaDialect']);
        $this->assertEquals('http://example.com/docs', $parsed['externalDocs']['url']);
        $this->assertEquals('user', $parsed['tags'][0]['name']);
        $this->assertTrue(isset($parsed['security'][0]['apiKey']));
        
        $this->assertEquals('Test API', $parsed['info']['title']);
        $this->assertEquals('1.0.0', $parsed['info']['version']);
        $this->assertEquals('A test api.', $parsed['info']['description']);
        $this->assertEquals('http://example.com/tos', $parsed['info']['termsOfService']);
        $this->assertEquals('API Support', $parsed['info']['contact']['name']);
        $this->assertEquals('support@example.com', $parsed['info']['contact']['email']);
        $this->assertEquals('Apache 2.0', $parsed['info']['license']['name']);

        // Clean up
        array_map('unlink', glob("$tmpDir/*.*"));
        rmdir($tmpDir);
    }
}
