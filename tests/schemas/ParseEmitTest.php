<?php

declare(strict_types=1);

namespace Cdd\Tests\Schemas;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $code = "<?php\nclass User {\n    public int \$id;\n    public string \$name;\n    public ?string \$email;\n}\n";
        
        $classes = \Cdd\Classes\parse($code);
        $this->assertEquals(1, count($classes));
        
        $schema = \Cdd\Schemas\parse($classes[0]['node']);
        
        $this->assertEquals('object', $schema['type']);
        $this->assertEquals('integer', $schema['properties']['id']['type']);
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertEquals('string', $schema['properties']['email']['type']);
        $this->assertTrue($schema['properties']['email']['nullable']);
        $this->assertTrue(in_array('id', $schema['required']));
        $this->assertTrue(in_array('name', $schema['required']));
        
        $emitted = \Cdd\Schemas\emit('User', $schema);
        $this->assertTrue(strpos($emitted, 'class User {') !== false);
        $this->assertTrue(strpos($emitted, 'public int $id;') !== false);
        $this->assertTrue(strpos($emitted, 'public string $name;') !== false);
        $this->assertTrue(strpos($emitted, 'public ?string $email;') !== false);
    }

    public function testDocblockParsing() {
        $code = "<?php\n/**\n * My User model\n * @xml nodeType element\n * @discriminator propertyName role\n * @discriminator defaultMapping Guest\n * @discriminator mapping admin AdminUser\n */\nclass User {\n    public string \$role;\n}\n";
        $classes = \Cdd\Classes\parse($code);
        $schema = \Cdd\Schemas\parse($classes[0]['node']);
        
        $this->assertEquals('My User model', $schema['description']);
        $this->assertEquals('element', $schema['xml']['nodeType']);
        $this->assertEquals('role', $schema['discriminator']['propertyName']);
        $this->assertEquals('Guest', $schema['discriminator']['defaultMapping']);
        $this->assertEquals('AdminUser', $schema['discriminator']['mapping']['admin']);
        
        $emitted = \Cdd\Schemas\emit('User', $schema);
        $this->assertTrue(strpos($emitted, '/**') !== false);
        $this->assertTrue(strpos($emitted, '* My User model') !== false);
        $this->assertTrue(strpos($emitted, '* @xml nodeType element') !== false);
        $this->assertTrue(strpos($emitted, '* @discriminator propertyName role') !== false);
        $this->assertTrue(strpos($emitted, '* @discriminator defaultMapping Guest') !== false);
        $this->assertTrue(strpos($emitted, '* @discriminator mapping admin AdminUser') !== false);
    }
}
