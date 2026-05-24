<?php

namespace Cdd\Tests\Controllers;

class ParseTest extends \Cdd\Tests\Framework\TestCase
{
    public function testParseDocBlocks()
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
            public function createUser() {
            }

            /**
             * Just a summary
             * @tags Single
             */
            public function justSummary() {
            }
        }';

        $ops = \Cdd\Controllers\parse($code);
        $this->assertTrue(isset($ops['createUser']));

        $op = $ops['createUser'];
        $this->assertEquals('Create a user', $op['summary']);
        $this->assertEquals("This operation creates a new user.\nIt is very useful.", $op['description']);
        $this->assertEquals(2, count($op['tags']));
        $this->assertEquals('Users', $op['tags'][0]);
        $this->assertEquals('Accounts', $op['tags'][1]);
        $this->assertEquals('https://docs.example.com/user', $op['externalDocs']['url']);
        $this->assertEquals('More details', $op['externalDocs']['description']);

        $this->assertTrue(isset($op['callbacks']['onData']));
        $this->assertTrue(isset($op['callbacks']['onData']['http://notificationUrl']));

        $this->assertTrue(isset($op['responses']['200']['links']['getAccountById']));
        $this->assertEquals('getAccount', $op['responses']['200']['links']['getAccountById']['operationId']);

        $this->assertTrue(isset($ops['justSummary']));
        $this->assertEquals('Just a summary', $ops['justSummary']['summary']);
        $this->assertTrue(!isset($ops['justSummary']['description']));
    }

    public function testParseInvalidCode()
    {
        $this->assertEquals([], \Cdd\Controllers\parse('<?php class {'));
    }
}
