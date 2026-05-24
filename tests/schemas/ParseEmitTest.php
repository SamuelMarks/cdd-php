<?php

declare(strict_types=1);

namespace Cdd\Tests\Schemas;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testParseAndEmit()
    {
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
        $this->assertTrue(strpos($emitted, 'class User extends \Illuminate\Database\Eloquent\Model {') !== false);
        $this->assertTrue(strpos($emitted, "'id',") !== false);
        $this->assertTrue(strpos($emitted, "'name',") !== false);
        $this->assertTrue(strpos($emitted, "'email',") !== false);
        $this->assertTrue(strpos($emitted, "'id' => 'integer',") !== false);
    }

    public function testDocblockParsing()
    {
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

    public function testParseOptionals()
    {
        $code = "<?php
/**
 * @discriminator defaultMapping Guest
 * @discriminator mapping admin AdminUser
 */
class DefaultDisc {
    protected \$hidden;
    public mixed \$mixedProp;
    public MyType \$customType;
} class EmptyClass {}
";
        $classes = \Cdd\Classes\parse($code);
        $schema = \Cdd\Schemas\parse($classes[0]['node']);
        $this->assertEquals('type', $schema['discriminator']['propertyName']); // 45, 56

        $this->assertEquals('string', $schema['properties']['mixedProp']['type']); // 94
        $this->assertEquals('#/components/schemas/MyType', $schema['properties']['customType']['$ref']); // 98
        $this->assertTrue(empty($schema['properties']['hidden']));

        $schemaEmpty = \Cdd\Schemas\parse($classes[1]['node']);
        $this->assertTrue(empty($schemaEmpty['properties']));
    }

    public function testValidationErrors()
    {
        $tests = [
            ['input' => ['properties' => 123], 'error' => 'Schema "properties" must be a map'],
            ['input' => ['allOf' => 123], 'error' => 'Schema "allOf" must be an array'],
            ['input' => ['anyOf' => 123], 'error' => 'Schema "anyOf" must be an array'],
            ['input' => ['oneOf' => 123], 'error' => 'Schema "oneOf" must be an array'],
            ['input' => ['discriminator' => 123], 'error' => 'Discriminator must be an object'],
            ['input' => ['discriminator' => ['propertyName' => 'a', 'mapping' => 123]], 'error' => 'Discriminator "mapping" must be a map'],
            ['input' => ['discriminator' => ['propertyName' => 'a', 'defaultMapping' => 123]], 'error' => 'Discriminator "defaultMapping" must be a string'],
            ['input' => ['xml' => 123], 'error' => 'XML must be an object'],
            ['input' => ['xml' => ['namespace' => 123]], 'error' => 'XML "namespace" must be a string'],
            ['input' => ['xml' => ['prefix' => 123]], 'error' => 'XML "prefix" must be a string'],
            ['input' => ['xml' => ['attribute' => 123]], 'error' => 'XML "attribute" must be a boolean'],
            ['input' => ['xml' => ['wrapped' => 123]], 'error' => 'XML "wrapped" must be a boolean'],
            ['input' => ['xml' => ['nodeType' => 123]], 'error' => 'XML "nodeType" must be a string'],
            ['input' => ['xml' => ['nodeType' => 'invalid']], 'error' => 'XML "nodeType" must be one of: element, attribute, text, cdata, none'],
        ];

        foreach ($tests as $test) {
            $caught = false;
            try {
                \Cdd\Schemas\validateSchemaOrReferenceObject($test['input']);
            } catch (\RuntimeException $e) {
                $this->assertEquals($test['error'], $e->getMessage());
                $caught = true;
            }
            $this->assertTrue($caught, "Expected exception: {$test['error']}");
        }

        // boolean schema
        \Cdd\Schemas\validateSchemaOrReferenceObject(true);

        // schema with allOf/anyOf/oneOf/not and externalDocs
        \Cdd\Schemas\validateSchemaOrReferenceObject([
            'allOf' => [['type' => 'string']],
            'anyOf' => [['type' => 'string']],
            'oneOf' => [['type' => 'string']],
            'not' => ['type' => 'string'],
            'externalDocs' => ['url' => 'http://example.com']
        ]);
        $this->assertTrue(true);
    }
    public function testParseDiscriminatorMappingOnly()
    {
        $code = "<?php
/**
 * @discriminator mapping admin AdminUser
 */
class MappingOnly { }
";
        $classes = \Cdd\Classes\parse($code);
        $schema = \Cdd\Schemas\parse($classes[0]['node']);
        $this->assertEquals('type', $schema['discriminator']['propertyName']);
    }

    public function testParseUnionType()
    {
        $code = "<?php
class UnionTypeClass {
    public string|int \$unionProp;
}
";
        $classes = \Cdd\Classes\parse($code);
        $schema = \Cdd\Schemas\parse($classes[0]['node']);
        // UnionType parses as 'mixed' fallback in current logic because it's not an Identifier/Name
        $this->assertEquals('string', $schema['properties']['unionProp']['type']);
    }

    public function testParseNullableUnionType()
    {
        $code = "<?php
class NullableUnionTypeClass {
    public int|string|null \$nullableUnionProp;
    public ?\Closure \$closureProp;
}
";
        $classes = \Cdd\Classes\parse($code);
        $schema = \Cdd\Schemas\parse($classes[0]['node']);
        $this->assertEquals('string', $schema['properties']['nullableUnionProp']['type']);
        $this->assertTrue(!isset($schema['properties']['nullableUnionProp']['nullable']));

        $this->assertEquals('#/components/schemas/Closure', $schema['properties']['closureProp']['$ref']);
    }

    public function testExtraXMLValidation()
    {
        $caught = false;
        try {
            \Cdd\Schemas\validateSchemaOrReferenceObject(['xml' => ['nodeType' => 'element', 'attribute' => true]]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('XML "attribute" MUST NOT be present if "nodeType" is present', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        $caught = false;
        try {
            \Cdd\Schemas\validateSchemaOrReferenceObject(['xml' => ['nodeType' => 'element', 'wrapped' => true]]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('XML "wrapped" MUST NOT be present if "nodeType" is present', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        $caught = false;
        try {
            \Cdd\Schemas\validateSchemaOrReferenceObject(['xml' => ['name' => 123]]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('XML "name" must be a string', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);
    }
}
