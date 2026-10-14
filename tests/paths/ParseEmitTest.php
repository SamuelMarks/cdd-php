<?php

/**
 * Tests for OpenAPI Paths and Path Item Object parsing, emission, and validation (`\Cdd\Paths`).
 *
 * @package Cdd\Tests\Paths
 */

declare(strict_types=1);

namespace Cdd\Tests\Paths;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Paths\parse`, `\Cdd\Paths\emit`, `\Cdd\Paths\emit_modular`, and path item validation.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting an `ApiController` from OpenAPI Paths.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $pathItems = [
            '/users' => [
                'get' => ['operationId' => 'listUsers'],
                'post' => ['operationId' => 'createUser'],
            ],
        ];

        $paths = \Cdd\Paths\parse($pathItems);

        $emitted = \Cdd\Paths\emit($paths);
        $this->assertTrue(strpos($emitted, 'class ApiController {') !== false);
        $this->assertTrue(strpos($emitted, 'public function listUsers() {') !== false);
        $this->assertTrue(strpos($emitted, 'public function createUser() {') !== false);
    }

    /**
     * Tests emitting into an existing `ApiController` class while preserving custom methods.
     *
     * @return void
     */
    public function testEmitWithExistingCode(): void
    {
        $paths = ['/api/users' => ['get' => ['operationId' => 'getUsers']]];
        $existing = "<?php

class ApiController {
    // Some comment
    public function custom() {}
}
";
        $emitted = \Cdd\Paths\emit($paths, $existing);
        $this->assertTrue(strpos($emitted, '// Some comment') !== false);
        $this->assertTrue(strpos($emitted, 'public function custom() {}') !== false);
        $this->assertTrue(strpos($emitted, 'public function getUsers') !== false);
    }

    /**
     * Tests emitting `additionalOperations` in `Cdd\Paths\emit`.
     *
     * @return void
     */
    public function testEmitAdditionalOperations(): void
    {
        $paths = [
            '/extra' => [
                'additionalOperations' => [
                    'm-SEARCH' => ['operationId' => 'mSearchExtra'],
                    'PROPFIND' => [],
                ],
            ],
        ];
        $emitted = \Cdd\Paths\emit($paths);
        $this->assertTrue(strpos($emitted, 'public function mSearchExtra() {') !== false);
        $this->assertTrue(strpos($emitted, 'public function propfindextra() {') !== false);

        $existing = '<?php class ApiController { }';
        $emitted2 = \Cdd\Paths\emit($paths, $existing);
        $this->assertTrue(strpos($emitted2, 'public function mSearchExtra() {') !== false);

        $existingNoBrace = '<?php class ApiController ';
        $emitted3 = \Cdd\Paths\emit($paths, $existingNoBrace);
        $this->assertTrue(strpos($emitted3, 'public function mSearchExtra() {') !== false);
    }

    /**
     * Tests fallback operationId generation when `operationId` is omitted.
     *
     * @return void
     */
    public function testEmitMissingOperationId(): void
    {
        $paths = [
            '/no-op' => [
                'get' => [],
            ],
        ];
        $emitted = \Cdd\Paths\emit($paths);
        $this->assertTrue(strpos($emitted, 'public function getnoop() {') !== false);

        $existingNoBrace = '<?php class ApiController ';
        $emitted2 = \Cdd\Paths\emit($paths, $existingNoBrace);
        $this->assertTrue(strpos($emitted2, 'public function getnoop() {') !== false);
    }

    /**
     * Tests validation errors for Path Item Objects.
     *
     * @return void
     */
    public function testValidatePathItemObjectFailures(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Paths\validatePathItemObject('not array');
        }, \RuntimeException::class, 'Path Item must be an object');

        $this->assertThrows(function (): void {
            \Cdd\Paths\validatePathItemObject(['$ref' => 123]);
        }, \RuntimeException::class, 'Path Item "$ref" must be a string');

        $this->assertThrows(function (): void {
            \Cdd\Paths\validatePathItemObject(['summary' => 123]);
        }, \RuntimeException::class, 'Path Item "summary" must be a string');

        $this->assertThrows(function (): void {
            \Cdd\Paths\validatePathItemObject(['description' => 123]);
        }, \RuntimeException::class, 'Path Item "description" must be a string');

        $this->assertThrows(function (): void {
            \Cdd\Paths\validatePathItemObject(['additionalOperations' => 'not array']);
        }, \RuntimeException::class, 'Path Item "additionalOperations" must be a map');

        $this->assertThrows(function (): void {
            \Cdd\Paths\validatePathItemObject(['additionalOperations' => ['GET' => []]]);
        }, \RuntimeException::class, 'Path Item "additionalOperations" MUST NOT contain any entry for fixed methods (e.g., GET)');

        $this->assertThrows(function (): void {
            \Cdd\Paths\validatePathItemObject(['servers' => 'not array']);
        }, \RuntimeException::class, 'Path Item "servers" must be an array');

        $this->assertThrows(function (): void {
            \Cdd\Paths\validatePathItemObject(['parameters' => 'not array']);
        }, \RuntimeException::class, 'Path Item "parameters" must be an array');

        $this->assertThrows(function (): void {
            \Cdd\Paths\validatePathItemObject([
                'parameters' => [
                    ['name' => 'id', 'in' => 'query', 'schema' => []],
                    ['name' => 'id', 'in' => 'query', 'schema' => []],
                ],
            ]);
        }, \RuntimeException::class, 'Path Item parameters list MUST NOT include duplicated parameters');
    }

    /**
     * Tests validating a valid Path Item Object and emitting modular controllers.
     *
     * @return void
     */
    public function testValidatePathItemObjectValid(): void
    {
        $this->assertDoesNotThrow(function (): void {
            \Cdd\Paths\validatePathItemObject([
                '$ref' => '#/paths/~1foo',
                'summary' => 'summary',
                'description' => 'desc',
                'servers' => [['url' => 'http://example.com']],
                'parameters' => [
                    ['name' => 'id', 'in' => 'query', 'schema' => []],
                    ['name' => 'id', 'in' => 'header', 'schema' => []],
                ],
            ]);
        });
        $modular = \Cdd\Paths\emit_modular([
            '/users' => [
                'summary' => 'Users path',
                'get' => ['operationId' => 'listUsers'],
                'additionalOperations' => [
                    'CUSTOM' => ['operationId' => 'customUsers'],
                ],
            ],
        ]);
        $this->assertArrayHasKey('ListUsersController.php', $modular);
        $this->assertEquals([], \Cdd\Paths\emit_modular_single('INVALID', '/users', []));
    }
}
