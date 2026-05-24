<?php

declare(strict_types=1);

namespace Cdd\Tests\Info;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testParseAndEmit()
    {
        $info = \Cdd\Info\parse('My API', '2.0.0', 'Does things');
        $this->assertEquals('My API', $info['title']);
        $this->assertEquals('2.0.0', $info['version']);
        $this->assertEquals('Does things', $info['description']);

        $emitted = \Cdd\Info\emit($info);
        $this->assertTrue(strpos($emitted, '* Does things') !== false);
    }

    public function testTagSummaryNotString()
    {
        try {
            \Cdd\Info\validateTagObject(['name' => 'test', 'summary' => 123]);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertEquals('Tag "summary" must be a string', $e->getMessage());
        }
    }
    public function testTagParentNotString()
    {
        try {
            \Cdd\Info\validateTagObject(['name' => 'test', 'parent' => 123]);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertEquals('Tag "parent" must be a string', $e->getMessage());
        }
    }
    public function testTagKindNotString()
    {
        try {
            \Cdd\Info\validateTagObject(['name' => 'test', 'kind' => 123]);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertEquals('Tag "kind" must be a string', $e->getMessage());
        }
    }

    public function testValidContactAndLicense()
    {
        \Cdd\Info\validateContactObject(['name' => 'A', 'url' => 'B', 'email' => 'C']);
        \Cdd\Info\validateLicenseObject(['name' => 'MIT', 'identifier' => 'MIT']);
        $this->assertTrue(true);
    }
}
