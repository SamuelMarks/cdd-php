<?php

namespace Cdd\Tests\Webhooks;

class ParseEmitTest extends \Cdd\Tests\Framework\TestCase
{
    public function testParseAndEmit()
    {
        $webhooks = [
            'myWebhook' => [
                'post' => [
                    'operationId' => 'myWebhookHandler',
                    'responses' => [
                        '200' => ['description' => 'Success']
                    ]
                ]
            ]
        ];

        $emitted = \Cdd\Webhooks\emit($webhooks);
        $this->assertTrue(strpos($emitted, 'interface WebhookHandlers') !== false);
        $this->assertTrue(strpos($emitted, 'public function myWebhookHandler(array $request): array;') !== false);

        $parsed = \Cdd\Webhooks\parse($emitted);
        $this->assertTrue(isset($parsed['myWebhookHandler']));
        $this->assertTrue(isset($parsed['myWebhookHandler']['post']));
    }

    public function testEmitEmpty()
    {
        $this->assertEquals('', \Cdd\Webhooks\emit([]));
    }

    public function testEmitRefAndInvalidMethod()
    {
        $webhooks = [
            'hook1' => ['$ref' => '#/components/webhooks/hook1'],
            'hook2' => ['invalid_method' => []]
        ];
        $emitted = \Cdd\Webhooks\emit($webhooks);
        $this->assertTrue(strpos($emitted, "// Webhook 'hook1' uses reference: #/components/webhooks/hook1") !== false);
        $this->assertTrue(strpos($emitted, 'invalid_method') === false);
    }

    public function testParseInvalidCode()
    {
        $this->assertEquals([], \Cdd\Webhooks\parse('<?php interface {'));
    }
}
