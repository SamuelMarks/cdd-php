<?php

namespace Cdd\Tests\Mocks;

class SyncMocksTest extends \Cdd\Tests\Framework\TestCase {
    public function testMockUpdatesSchema() {
        $tmpDir = sys_get_temp_dir() . '/cdd-php-test-' . uniqid();
        mkdir($tmpDir);

        // Create a mock
        $mockCode = "<?php
return [
    'petExample' => [
        'dataValue' => [
            'id' => 123,
            'name' => 'Fido',
            'isGoodBoy' => true,
            'tags' => ['dog', 'friendly']
        ]
    ]
];
";
        file_put_contents("$tmpDir/mocks.php", $mockCode);
        file_put_contents("$tmpDir/Models.php", "<?php\n\n");
        file_put_contents("$tmpDir/routes.php", "<?php\n\n");

        // Run sync
        $baseDir = dirname(__DIR__, 2);
        exec("php $baseDir/bin/cdd-php sync -d $tmpDir", $output, $returnVar);

        $this->assertEquals(0, $returnVar);
        
        $json = file_get_contents("$tmpDir/openapi.json");
        $openapi = json_decode($json, true);

        $this->assertTrue(isset($openapi['components']['examples']['petExample']));
        $this->assertTrue(isset($openapi['components']['schemas']['PetExampleModel']));
        
        $schema = $openapi['components']['schemas']['PetExampleModel'];
        $this->assertEquals('object', $schema['type']);
        $this->assertEquals('integer', $schema['properties']['id']['type']);
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertEquals('boolean', $schema['properties']['isGoodBoy']['type']);
        $this->assertEquals('array', $schema['properties']['tags']['type']);
        $this->assertEquals('string', $schema['properties']['tags']['items']['type']);

        // Clean up
        array_map('unlink', glob("$tmpDir/*.*"));
        rmdir($tmpDir);
    }
}
