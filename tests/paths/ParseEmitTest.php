<?php

declare(strict_types=1);

namespace Cdd\Tests\Paths;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    protected function assertThrows(\Closure $closure, string $exceptionClass, ?string $exceptionMessage = null)
    {
        try {
            $closure();
        } catch (\Throwable $e) {
            if (!($e instanceof $exceptionClass)) {
                throw new \Exception("Expected exception $exceptionClass, got " . get_class($e));
            }
            if ($exceptionMessage !== null && $e->getMessage() !== $exceptionMessage) {
                throw new \Exception("Expected exception message '$exceptionMessage', got '{$e->getMessage()}'");
            }
            return;
        }
        throw new \Exception("Expected exception $exceptionClass was not thrown");
    }

    public function testParseAndEmit()
    {
        $pathItems = [
            '/users' => [
                'get' => ['operationId' => 'listUsers'],
                'post' => ['operationId' => 'createUser'],
            ]
        ];

        $paths = \Cdd\Paths\parse($pathItems);

        $emitted = \Cdd\Paths\emit($paths);
        $this->assertTrue(strpos($emitted, 'class ApiController {') !== false);
        $this->assertTrue(strpos($emitted, 'public function listUsers() {') !== false);
        $this->assertTrue(strpos($emitted, 'public function createUser() {') !== false);
    }

    public function testEmitWithExistingCode()
    {
        $paths = ['/api/users' => ['get' => ['operationId' => 'getUsers']]];
        $existing = "<?php\n\nclass ApiController {\n    // Some comment\n    public function custom() {}\n}\n";
        $emitted = \Cdd\Paths\emit($paths, $existing);
        $this->assertTrue(strpos($emitted, '// Some comment') !== false);
        $this->assertTrue(strpos($emitted, 'public function custom() {}') !== false);
        $this->assertTrue(strpos($emitted, 'public function getUsers') !== false);
    }

    public function testEmitAdditionalOperations()
    {
        $paths = [
            '/extra' => [
                'additionalOperations' => [
                    'm-SEARCH' => ['operationId' => 'mSearchExtra'],
                    'PROPFIND' => [] // Will generate operationId: propfindextra
                ]
            ]
        ];
        $emitted = \Cdd\Paths\emit($paths);
        $this->assertTrue(strpos($emitted, 'public function mSearchExtra() {') !== false);
        $this->assertTrue(strpos($emitted, 'public function propfindextra() {') !== false);

        // Test with existing code
        $existing = "<?php class ApiController { }";
        $emitted2 = \Cdd\Paths\emit($paths, $existing);
        $this->assertTrue(strpos($emitted2, 'public function mSearchExtra() {') !== false);

        // Test with existing code but missing closing brace
        $existingNoBrace = "<?php class ApiController ";
        $emitted3 = \Cdd\Paths\emit($paths, $existingNoBrace);
        $this->assertTrue(strpos($emitted3, 'public function mSearchExtra() {') !== false);
    }

    public function testEmitMissingOperationId()
    {
        $paths = [
            '/no-op' => [
                'get' => [] // generates getnoop
            ]
        ];
        $emitted = \Cdd\Paths\emit($paths);
        $this->assertTrue(strpos($emitted, 'public function getnoop() {') !== false);

        $existingNoBrace = "<?php class ApiController ";
        $emitted2 = \Cdd\Paths\emit($paths, $existingNoBrace);
        $this->assertTrue(strpos($emitted2, 'public function getnoop() {') !== false);
    }

    public function testValidatePathItemObjectFailures()
    {
        $this->assertThrows(function () {
            \Cdd\Paths\validatePathItemObject('not array');
        }, \RuntimeException::class, 'Path Item must be an object');

        $this->assertThrows(function () {
            \Cdd\Paths\validatePathItemObject(['$ref' => 123]);
        }, \RuntimeException::class, 'Path Item "$ref" must be a string');

        $this->assertThrows(function () {
            \Cdd\Paths\validatePathItemObject(['summary' => 123]);
        }, \RuntimeException::class, 'Path Item "summary" must be a string');

        $this->assertThrows(function () {
            \Cdd\Paths\validatePathItemObject(['description' => 123]);
        }, \RuntimeException::class, 'Path Item "description" must be a string');

        $this->assertThrows(function () {
            \Cdd\Paths\validatePathItemObject(['additionalOperations' => 'not array']);
        }, \RuntimeException::class, 'Path Item "additionalOperations" must be a map');

        $this->assertThrows(function () {
            \Cdd\Paths\validatePathItemObject(['additionalOperations' => ['GET' => []]]);
        }, \RuntimeException::class, 'Path Item "additionalOperations" MUST NOT contain any entry for fixed methods (e.g., GET)');

        $this->assertThrows(function () {
            \Cdd\Paths\validatePathItemObject(['servers' => 'not array']);
        }, \RuntimeException::class, 'Path Item "servers" must be an array');

        $this->assertThrows(function () {
            \Cdd\Paths\validatePathItemObject(['parameters' => 'not array']);
        }, \RuntimeException::class, 'Path Item "parameters" must be an array');

        $this->assertThrows(function () {
            \Cdd\Paths\validatePathItemObject([
                'parameters' => [
                    ['name' => 'id', 'in' => 'query', 'schema' => []],
                    ['name' => 'id', 'in' => 'query', 'schema' => []]
                ]
            ]);
        }, \RuntimeException::class, 'Path Item parameters list MUST NOT include duplicated parameters');
    }

    public function testValidatePathItemObjectValid()
    {
        \Cdd\Paths\validatePathItemObject([
            '$ref' => '#/paths/~1foo',
            'summary' => 'summary',
            'description' => 'desc',
            'servers' => [['url' => 'http://example.com']],
            'parameters' => [
                ['name' => 'id', 'in' => 'query', 'schema' => []],
                ['name' => 'id', 'in' => 'header', 'schema' => []]
            ]
        ]);
        $this->assertTrue(true);
    }
}
