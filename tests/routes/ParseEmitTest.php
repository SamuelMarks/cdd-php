<?php

declare(strict_types=1);

namespace Cdd\Tests\Routes;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $code = "<?php\n\nRoute::get('/api/users', 'UserController@index');\nRoute::post('/api/users', 'UserController@store');\n";
        
        $routes = \Cdd\Routes\parse($code);
        $this->assertEquals(1, count($routes)); // One path
        $this->assertTrue(isset($routes['/api/users']['get']));
        $this->assertTrue(isset($routes['/api/users']['post']));
        
        $emitted = \Cdd\Routes\emit($routes);
        $this->assertTrue(strpos($emitted, "Route::get('/api/users'") !== false);
        $this->assertTrue(strpos($emitted, "Route::post('/api/users'") !== false);
    }

    public function testEmitWithExistingCode() {
        $routes = ['/api/users' => ['get' => ['operationId' => 'UserController@index']]];
        $existing = "<?php\n// Existing comment\nRoute::get('/api/users', 'UserController@index');\n";
        $emitted = \Cdd\Routes\emit($routes, $existing);
        $this->assertEquals($existing, $emitted); // Should be unchanged

        $routes['/api/new'] = ['post' => ['operationId' => 'NewController@store']];
        $emitted2 = \Cdd\Routes\emit($routes, $existing);
        $this->assertTrue(strpos($emitted2, "// Existing comment") !== false);
        $this->assertTrue(strpos($emitted2, "Route::post('/api/new', 'NewController@store');") !== false);
    }
}
