<?php

/**
 * Tests for parsing PHP Controller classes and docblocks (`\Cdd\Controllers\parse`).
 *
 * @package Cdd\Tests\Controllers
 */

declare(strict_types=1);

namespace Cdd\Tests\Controllers;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests extracting OpenAPI operation metadata, parameters, requestBody, and responses from controllers.
 */
class ParseTest extends TestCase
{
    /**
     * Tests parsing controller docblocks and method signatures.
     *
     * @return void
     */
    public function testParseDocBlocks(): void
    {
        $code = '<?php
        class ApiController {
            /**
             * Create a user
             *
             * This operation creates a new user.
             * It is very useful.
             * @tags Users, Accounts
             * @externalDocs https://docs.example.com/user More details
             * @oas-callback onData {"http://notificationUrl":{"post":{"requestBody":{"description":"Event data"}}}}
             * @oas-link 200 getAccountById {"operationId":"getAccount","parameters":{"accountId":"$response.body#/id"}}
             */
            public function createUser(int $orgId, ?string $filter, User $body): User {
            }

            /**
             * Just a summary
             * @tags Single
             */
            public function justSummary(): int {
            }
        }';

        $ops = \Cdd\Controllers\parse($code);
        $this->assertTrue(isset($ops['createUser']));

        $op = $ops['createUser'];
        $this->assertEquals('Create a user', $op['summary']);
        $this->assertEquals("This operation creates a new user.
It is very useful.", $op['description']);
        $this->assertEquals(2, count($op['tags']));
        $this->assertEquals('Users', $op['tags'][0]);
        $this->assertEquals('Accounts', $op['tags'][1]);
        $this->assertEquals('https://docs.example.com/user', $op['externalDocs']['url']);
        $this->assertEquals('More details', $op['externalDocs']['description']);

        $this->assertTrue(isset($op['callbacks']['onData']));
        $this->assertTrue(isset($op['callbacks']['onData']['http://notificationUrl']));

        $this->assertTrue(isset($op['responses']['200']['links']['getAccountById']));
        $this->assertEquals('getAccount', $op['responses']['200']['links']['getAccountById']['operationId']);
        $this->assertEquals('#/components/schemas/User', $op['responses']['200']['content']['application/json']['schema']['$ref']);
        $this->assertEquals(2, count($op['parameters']));
        $this->assertEquals('orgId', $op['parameters'][0]['name']);
        $this->assertTrue($op['parameters'][0]['required']);
        $this->assertEquals('filter', $op['parameters'][1]['name']);
        $this->assertFalse($op['parameters'][1]['required']);
        $this->assertEquals('#/components/schemas/User', $op['requestBody']['content']['application/json']['schema']['$ref']);

        $this->assertTrue(isset($ops['justSummary']));
        $this->assertEquals('Just a summary', $ops['justSummary']['summary']);
        $this->assertTrue(!isset($ops['justSummary']['description']));
        $this->assertEquals('integer', $ops['justSummary']['responses']['200']['content']['application/json']['schema']['type']);
    }

    /**
     * Tests parsing syntactically invalid PHP controller code.
     *
     * @return void
     */
    public function testParseInvalidCode(): void
    {
        $this->assertEquals([], \Cdd\Controllers\parse('<?php class {'));
    }
}
