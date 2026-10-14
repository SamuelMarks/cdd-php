<?php

/**
 * Emits PHP interfaces for incoming OpenAPI Webhooks.
 *
 * @package Cdd\Webhooks
 */

declare(strict_types=1);

namespace Cdd\Webhooks;

/**
 * Emits abstract interfaces for incoming Webhooks defined in OpenAPI.
 *
 * @param array<string, array<string, mixed>> $webhooks Map of webhook names to Path Item Objects.
 * @param string $existingCode Optional existing PHP code.
 * @return string The emitted WebhookHandlers interface code.
 */
function emit(array $webhooks, string $existingCode = ''): string
{
    if (empty($webhooks)) {
        return '';
    }

    $code = "<?php

/**
 * Abstract handlers for incoming Webhooks.
 */
";
    $code .= "interface WebhookHandlers {
";

    $validMethods = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'];

    foreach ($webhooks as $name => $pathItem) {
        if (isset($pathItem['$ref'])) {
            $code .= "    // Webhook '{$name}' uses reference: {$pathItem['$ref']}
";
            continue;
        }
        foreach ($pathItem as $method => $operation) {
            $methodStr = strtolower((string) $method);
            if (!in_array($methodStr, $validMethods, true)) {
                continue;
            }
            $opId = $operation['operationId'] ?? "handle_{$name}_{$methodStr}";
            $code .= "    public function {$opId}(array \$request): array;
";
        }
    }

    $code .= "}
";
    return $code;
}
