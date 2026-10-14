<?php

/**
 * Tests for OpenAPI Webhook interface parsing and emission (`\Cdd\Webhooks`).
 *
 * @package Cdd\Tests\Webhooks
 */

declare(strict_types=1);

namespace Cdd\Tests\Webhooks;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Webhooks\parse` and `\Cdd\Webhooks\emit`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting `WebhookHandlers` interfaces.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $webhooks = [
            'myWebhook' => [
                'post' => [
                    'operationId' => 'myWebhookHandler',
                    'responses' => [
                        '200' => ['description' => 'Success'],
                    ],
                ],
            ],
            'orderCreated' => [
                'put' => [
                    'responses' => [
                        '200' => ['description' => 'Success'],
                    ],
                ],
            ],
        ];

        $emitted = \Cdd\Webhooks\emit($webhooks);
        $this->assertTrue(strpos($emitted, 'interface WebhookHandlers') !== false);
        $this->assertTrue(strpos($emitted, 'public function myWebhookHandler(array $request): array;') !== false);
        $this->assertTrue(strpos($emitted, 'public function handle_orderCreated_put(array $request): array;') !== false);

        $parsed = \Cdd\Webhooks\parse($emitted);
        $this->assertTrue(isset($parsed['myWebhookHandler']['post']));
        $this->assertTrue(isset($parsed['orderCreated']['put']));
    }

    /**
     * Tests emitting an empty webhooks array.
     *
     * @return void
     */
    public function testEmitEmpty(): void
    {
        $this->assertEquals('', \Cdd\Webhooks\emit([]));
    }

    /**
     * Tests emitting webhooks with `$ref` and ignoring invalid HTTP methods.
     *
     * @return void
     */
    public function testEmitRefAndInvalidMethod(): void
    {
        $webhooks = [
            'hook1' => ['$ref' => '#/components/webhooks/hook1'],
            'hook2' => ['invalid_method' => []],
        ];
        $emitted = \Cdd\Webhooks\emit($webhooks);
        $this->assertTrue(strpos($emitted, "// Webhook 'hook1' uses reference: #/components/webhooks/hook1") !== false);
        $this->assertTrue(strpos($emitted, 'invalid_method') === false);
    }

    /**
     * Tests parsing syntactically invalid PHP interface code.
     *
     * @return void
     */
    public function testParseInvalidCode(): void
    {
        $this->assertEquals([], \Cdd\Webhooks\parse('<?php interface {'));
    }
}
