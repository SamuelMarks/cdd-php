<?php

/**
 * Tests for OpenAPI Schema Object parsing, emission, and validation (`\Cdd\Schemas`).
 *
 * @package Cdd\Tests\Schemas
 */

declare(strict_types=1);

namespace Cdd\Tests\Schemas;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Schemas\parse`, `\Cdd\Schemas\emit`, and schema/discriminator/XML validation.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing a PHP class AST node into a Schema Object and emitting an Eloquent Model.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $code = "<?php
class User {
    public int \$id;
    public string \$name;
    public ?string \$email;
}
";

        $classes = \Cdd\Classes\parse($code);
        $this->assertEquals(1, count($classes));

        $schema = \Cdd\Schemas\parse($classes[0]['node']);

        $this->assertEquals('object', $schema['type']);
        $this->assertEquals('integer', $schema['properties']['id']['type']);
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertEquals('string', $schema['properties']['email']['type']);
        $this->assertTrue($schema['properties']['email']['nullable']);
        $this->assertTrue(in_array('id', $schema['required'], true));
        $this->assertTrue(in_array('name', $schema['required'], true));

        $emitted = \Cdd\Schemas\emit('User', $schema);
        $this->assertTrue(strpos($emitted, 'class User extends \Illuminate\Database\Eloquent\Model {') !== false);
        $this->assertTrue(strpos($emitted, "'id',") !== false);
        $this->assertTrue(strpos($emitted, "'name',") !== false);
        $this->assertTrue(strpos($emitted, "'email',") !== false);
        $this->assertTrue(strpos($emitted, "'id' => 'integer',") !== false);
    }

    /**
     * Tests parsing and emitting `@xml` and `@discriminator` docblock tags on schema classes.
     *
     * @return void
     */
    public function testDocblockParsing(): void
    {
        $code = "<?php
/**
 * My User model
 * @xml nodeType element
 * @discriminator propertyName role
 * @discriminator defaultMapping Guest
 * @discriminator mapping admin AdminUser
 */
class User {
    public string \$role;
}
";
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

    /**
     * Tests parsing mixed, custom class reference, and non-public properties.
     *
     * @return void
     */
    public function testParseOptionals(): void
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
        $this->assertEquals('type', $schema['discriminator']['propertyName']);
        $this->assertEquals('string', $schema['properties']['mixedProp']['type']);
        $this->assertEquals('#/components/schemas/MyType', $schema['properties']['customType']['$ref']);
        $this->assertTrue(empty($schema['properties']['hidden']));

        $schemaEmpty = \Cdd\Schemas\parse($classes[1]['node']);
        $this->assertTrue(empty($schemaEmpty['properties']));
    }

    /**
     * Tests validation errors and valid composition keywords on Schema Objects.
     *
     * @return void
     */
    public function testValidationErrors(): void
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
            $this->assertThrows(function () use ($test): void {
                \Cdd\Schemas\validateSchemaOrReferenceObject($test['input']);
            }, \Cdd\Exceptions\CddException::class, $test['error']);
        }

        $this->assertDoesNotThrow(function (): void {
            \Cdd\Schemas\validateSchemaOrReferenceObject(true);
            \Cdd\Schemas\validateSchemaOrReferenceObject([
                'allOf' => [['type' => 'string']],
                'anyOf' => [['type' => 'string']],
                'oneOf' => [['type' => 'string']],
                'not' => ['type' => 'string'],
                'externalDocs' => ['url' => 'http://example.com'],
            ]);
        });
    }

    /**
     * Tests default discriminator propertyName when only `@discriminator mapping` is present.
     *
     * @return void
     */
    public function testParseDiscriminatorMappingOnly(): void
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

    /**
     * Tests parsing PHP 8 union types on class properties.
     *
     * @return void
     */
    public function testParseUnionType(): void
    {
        $code = "<?php
class UnionTypeClass {
    public string|int \$unionProp;
}
";
        $classes = \Cdd\Classes\parse($code);
        $schema = \Cdd\Schemas\parse($classes[0]['node']);
        $this->assertEquals('string', $schema['properties']['unionProp']['type']);
    }

    /**
     * Tests parsing nullable union types and class-typed nullable properties.
     *
     * @return void
     */
    public function testParseNullableUnionType(): void
    {
        $code = "<?php
class NullableUnionTypeClass {
    public int|string|null \$nullableUnionProp;
    public ?\Closure \$closureProp;
}
";
        $classes = \Cdd\Classes\parse($code);
        $schema = \Cdd\Schemas\parse($classes[0]['node']);
        $this->assertEquals('integer', $schema['properties']['nullableUnionProp']['type']);
        $this->assertTrue($schema['properties']['nullableUnionProp']['nullable']);
        $this->assertEquals('#/components/schemas/Closure', $schema['properties']['closureProp']['$ref']);
    }

    /**
     * Tests XML Object validation rules when `nodeType` conflicts with `attribute` or `wrapped`.
     *
     * @return void
     */
    public function testExtraXMLValidation(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Schemas\validateSchemaOrReferenceObject(['xml' => ['nodeType' => 'element', 'attribute' => true]]);
        }, \Cdd\Exceptions\CddException::class, 'XML "attribute" MUST NOT be present if "nodeType" is present');

        $this->assertThrows(function (): void {
            \Cdd\Schemas\validateSchemaOrReferenceObject(['xml' => ['nodeType' => 'element', 'wrapped' => true]]);
        }, \Cdd\Exceptions\CddException::class, 'XML "wrapped" MUST NOT be present if "nodeType" is present');

        $this->assertThrows(function (): void {
            \Cdd\Schemas\validateSchemaOrReferenceObject(['xml' => ['name' => 123]]);
        }, \Cdd\Exceptions\CddException::class, 'XML "name" must be a string');

        // Roundtrip emitted Eloquent Model class with $ref, nullable, and required properties
        $fullSchema = [
            'type' => 'object',
            'description' => 'User schema',
            'required' => ['id', 'profile'],
            'properties' => [
                'id' => ['type' => 'integer'],
                'nickname' => ['type' => 'string', 'nullable' => true],
                'profile' => ['$ref' => '#/components/schemas/Profile'],
            ],
        ];
        $emittedModel = "<?php\n" . \Cdd\Schemas\emit('User', $fullSchema);
        $parsedClasses = \Cdd\Classes\parse($emittedModel);
        $roundtrippedSchema = \Cdd\Schemas\parse($parsedClasses[0]['node']);
        $this->assertEquals('integer', $roundtrippedSchema['properties']['id']['type']);
        $this->assertEquals('string', $roundtrippedSchema['properties']['nickname']['type']);
        $this->assertTrue($roundtrippedSchema['properties']['nickname']['nullable']);
        $this->assertEquals('#/components/schemas/Profile', $roundtrippedSchema['properties']['profile']['$ref']);
        $this->assertEquals(['id', 'profile'], $roundtrippedSchema['required']);

        // Also test parsing a pure Eloquent class with only $fillable and $casts (no @property tags)
        $eloquentOnly = "<?php\nclass Item extends Model {\n    protected \$fillable = ['qty', 'label'];\n    protected \$casts = ['qty' => 'integer'];\n}\n";
        $eloquentClasses = \Cdd\Classes\parse($eloquentOnly);
        $eloquentSchema = \Cdd\Schemas\parse($eloquentClasses[0]['node']);
        $this->assertEquals('integer', $eloquentSchema['properties']['qty']['type']);
        $this->assertEquals('string', $eloquentSchema['properties']['label']['type']);

        // Also test @property with ?Type and mixed
        $docPropClass = "<?php\n/**\n * @property ?int \$optInt\n * @property mixed \$anyVal\n */\nclass DocPropModel {}\n";
        $docPropParsed = \Cdd\Schemas\parse(\Cdd\Classes\parse($docPropClass)[0]['node']);
        $this->assertTrue($docPropParsed['properties']['optInt']['nullable']);
        $this->assertEquals('string', $docPropParsed['properties']['anyVal']['type']);
    }
}
