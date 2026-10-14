<?php

/**
 * Tests for inferring and updating OpenAPI Schema Objects from mock examples during `sync`.
 *
 * @package Cdd\Tests\Mocks
 */

declare(strict_types=1);

namespace Cdd\Tests\Mocks;

use Cdd\Cli\CddCli;
use Cdd\Tests\Framework\TestCase;

/**
 * Tests `CddCli::run(['cdd-php', 'sync', ...])` schema inference from `mocks.php`.
 */
class SyncMocksTest extends TestCase
{
    /**
     * Tests that mock `dataValue` entries infer and populate component schemas.
     *
     * @return void
     */
    public function testMockUpdatesSchema(): void
    {
        $tmpDir = sys_get_temp_dir() . '/cdd-php-test-' . uniqid();
        mkdir($tmpDir);

        $mockCode = "<?php
return [
    'petExample' => [
        'dataValue' => [
            'id' => 123,
            'weight' => 12.5,
            'name' => 'Fido',
            'isGoodBoy' => true,
            'tags' => ['dog', 'friendly']
        ]
    ]
];
";
        mkdir("$tmpDir/src", 0777, true);
        file_put_contents("$tmpDir/src/mocks.php", $mockCode);
        file_put_contents("$tmpDir/src/Models.php", "<?php

");
        file_put_contents("$tmpDir/src/routes.php", "<?php

");

        ob_start();
        $returnVar = CddCli::run(['cdd-php', 'sync', '-i', $tmpDir]);
        ob_end_clean();

        $this->assertEquals(0, $returnVar);

        $json = (string) file_get_contents("$tmpDir/openapi.json");
        $openapi = json_decode($json, true);

        $this->assertTrue(isset($openapi['components']['examples']['petExample']));
        $this->assertTrue(isset($openapi['components']['schemas']['PetExampleModel']));

        $schema = $openapi['components']['schemas']['PetExampleModel'];
        $this->assertEquals('object', $schema['type']);
        $this->assertEquals('integer', $schema['properties']['id']['type']);
        $this->assertEquals('number', $schema['properties']['weight']['type']);
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertEquals('boolean', $schema['properties']['isGoodBoy']['type']);
        $this->assertEquals('array', $schema['properties']['tags']['type']);
        $this->assertEquals('string', $schema['properties']['tags']['items']['type']);

        $this->removeDir($tmpDir);
    }
}
