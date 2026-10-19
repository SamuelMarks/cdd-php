<?php

/**
 * Tests forInfo Object, Contact Object, License Object, and Tag Object parsing/emission.
 *
 * @package Cdd\Tests\Info
 */

declare(strict_types=1);

namespace Cdd\Tests\Info;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Info\parse`, `\Cdd\Info\emit`, and Info/Contact/License/Tag validators.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting an OpenAPI Info Object.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $info = \Cdd\Info\parse('My API', '2.0.0', 'Does things');
        $this->assertEquals('My API', $info['title']);
        $this->assertEquals('2.0.0', $info['version']);
        $this->assertEquals('Does things', $info['description']);

        $emitted = \Cdd\Info\emit($info);
        $this->assertTrue(strpos($emitted, '* Does things') !== false);
        $this->assertEquals($info, \Cdd\Info\parse($emitted));

        $fullInfo = [
            'title' => 'Enterprise Service',
            'version' => '3.1.0',
            'summary' => 'Short summary',
            'description' => "Line 1\nLine 2",
            'termsOfService' => 'https://example.com/tos',
            'contact' => [
                'name' => 'Support Team',
                'url' => 'https://example.com/support',
                'email' => 'support@example.com',
            ],
            'license' => [
                'name' => 'Apache 2.0',
                'identifier' => 'Apache-2.0',
                'url' => 'https://www.apache.org/licenses/LICENSE-2.0',
            ],
        ];
        $emittedFull = \Cdd\Info\emit($fullInfo);
        $parsedFromDoc = \Cdd\Info\parse($emittedFull);
        $this->assertEquals($fullInfo, $parsedFromDoc);

        $validArrayInfo = [
            'title' => 'Valid Array Info',
            'version' => '1.0.0',
            'contact' => ['name' => 'Alice'],
            'license' => ['name' => 'MIT', 'identifier' => 'MIT'],
        ];
        $this->assertEquals($validArrayInfo, \Cdd\Info\parse($validArrayInfo));
    }

    /**
     * Tests Tag Object validation when summary is not a string.
     *
     * @return void
     */
    public function testTagSummaryNotString(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Info\validateTagObject(['name' => 'test', 'summary' => 123]);
        }, \RuntimeException::class, 'Tag "summary" must be a string');
    }

    /**
     * Tests Tag Object validation when parent is not a string.
     *
     * @return void
     */
    public function testTagParentNotString(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Info\validateTagObject(['name' => 'test', 'parent' => 123]);
        }, \RuntimeException::class, 'Tag "parent" must be a string');
    }

    /**
     * Tests Tag Object validation when kind is not a string.
     *
     * @return void
     */
    public function testTagKindNotString(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Info\validateTagObject(['name' => 'test', 'kind' => 123]);
        }, \RuntimeException::class, 'Tag "kind" must be a string');
    }

    /**
     * Tests valid Contact and License Object validation.
     *
     * @return void
     */
    public function testValidContactAndLicense(): void
    {
        $contact = ['name' => 'A', 'url' => 'B', 'email' => 'C'];
        $license = ['name' => 'MIT', 'identifier' => 'MIT'];
        $this->assertDoesNotThrow(function () use ($contact, $license): void {
            \Cdd\Info\validateContactObject($contact);
            \Cdd\Info\validateLicenseObject($license);
        });
    }
}
