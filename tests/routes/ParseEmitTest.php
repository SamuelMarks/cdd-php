<?php

/**
 * Tests for Laravel-style route parsing and emission (`\Cdd\Routes`).
 *
 * @package Cdd\Tests\Routes
 */

declare(strict_types=1);

namespace Cdd\Tests\Routes;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Routes\parse`, `\Cdd\Routes\emit`, and `\Cdd\Routes\emit_modular`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting `Route::get` and `Route::post` definitions.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $code = "<?php

Route::get('/api/users', 'UserController@index');
Route::post('/api/users', 'UserController@store');
Route::put('/api/users', [\Api\Controllers\UpdateUserController::class, '__invoke']);
Route::delete('/api/users', [\Api\Controllers\UserController::class, 'deleteUser']);
";

        $routes = \Cdd\Routes\parse($code);
        $this->assertEquals(1, count($routes));
        $this->assertTrue(isset($routes['/api/users']['get']));
        $this->assertTrue(isset($routes['/api/users']['post']));
        $this->assertEquals('updateUser', $routes['/api/users']['put']['operationId']);
        $this->assertEquals('deleteUser', $routes['/api/users']['delete']['operationId']);

        $emitted = \Cdd\Routes\emit($routes);
        $this->assertTrue(strpos($emitted, "Route::get('/api/users'") !== false);
        $this->assertTrue(strpos($emitted, "Route::post('/api/users'") !== false);
    }

    /**
     * Tests emitting routes while preserving existing comments and routes.
     *
     * @return void
     */
    public function testEmitWithExistingCode(): void
    {
        $routes = ['/api/users' => ['get' => ['operationId' => 'UserController@index']]];
        $existing = "<?php
// Existing comment
Route::get('/api/users', 'UserController@index');
";
        $emitted = \Cdd\Routes\emit($routes, $existing);
        $this->assertEquals($existing, $emitted);

        $routes['/api/new'] = ['post' => ['operationId' => 'NewController@store']];
        $emitted2 = \Cdd\Routes\emit($routes, $existing);
        $this->assertTrue(strpos($emitted2, '// Existing comment') !== false);
        $this->assertTrue(strpos($emitted2, "Route::post('/api/new', 'NewController@store');") !== false);

        $routesAdd = ['/api/custom' => ['additionalOperations' => ['CUSTOM' => ['operationId' => 'CustomController@action']]]];
        $emitted3 = \Cdd\Routes\emit($routesAdd, "<?php

Route::get('/api/users', 'UserController@index');
");
        $this->assertTrue(strpos($emitted3, "Route::custom('/api/custom', 'CustomController@action');") !== false);

        $emitted4 = \Cdd\Routes\emit($routesAdd, "<?php

Route::custom('/api/custom', 'CustomController@action');
");
        $this->assertEquals(1, substr_count($emitted4, "Route::custom('/api/custom'"));
    }

    /**
     * Tests parsing syntactically invalid PHP routes code.
     *
     * @return void
     */
    public function testParseSyntaxError(): void
    {
        $routes = \Cdd\Routes\parse('<?php class {');
        $this->assertEquals([], $routes);
    }

    /**
     * Tests parsing and emitting custom HTTP methods (`additionalOperations`).
     *
     * @return void
     */
    public function testParseAndEmitAdditionalOperations(): void
    {
        $code = "<?php

Route::custom_m('/api/custom', 'CustomController@action');
";
        $routes = \Cdd\Routes\parse($code);

        $this->assertTrue(isset($routes['/api/custom']['additionalOperations']['CUSTOM_M']));

        $emitted = \Cdd\Routes\emit($routes);
        $this->assertStringContainsString("Route::custom_m('/api/custom', 'custom_mapicustom');", $emitted);

        $existing = "<?php

Route::get('/api/users', 'UserController@index');
";
        $emittedExisting = \Cdd\Routes\emit($routes, $existing);
        $this->assertStringContainsString("Route::custom_m('/api/custom', 'custom_mapicustom');", $emittedExisting);
        $this->assertStringContainsString("Route::get('/api/users', 'UserController@index');", $emittedExisting);
    }

    /**
     * Tests emitting modular route files grouped by path prefix.
     *
     * @return void
     */
    public function testEmitModular(): void
    {
        $paths = [
            '/api/users' => ['get' => ['operationId' => 'getUsers']],
            '/api/posts' => ['post' => ['operationId' => 'createPost']],
            '/api/custom' => ['additionalOperations' => ['FOO' => ['operationId' => 'fooAction']]],
        ];

        $files = \Cdd\Routes\emit_modular($paths);

        $this->assertTrue(isset($files['ApiRoutes.php']));

        $content = $files['ApiRoutes.php'];
        $this->assertStringContainsString("Route::get('/api/users', [\Api\Controllers\GetUsersController::class, '__invoke']);", $content);
        $this->assertStringContainsString("Route::post('/api/posts', [\Api\Controllers\CreatePostController::class, '__invoke']);", $content);
        $this->assertTrue(strpos($content, 'FOO') === false);
    }
}
