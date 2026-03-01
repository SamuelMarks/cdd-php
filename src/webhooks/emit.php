<?php

declare(strict_types=1);

namespace Cdd\Webhooks;

/**
 * Emits abstract interfaces for incoming Webhooks defined in OpenAPI.
 *
 * @param array $webhooks
 * @param string $existingCode
 * @return string
 */
function emit(array $webhooks, string $existingCode = ''): string {
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
    
    foreach ($webhooks as $name => $pathItem) {
        if (isset($pathItem['$ref'])) {
            $code .= "    // Webhook '{$name}' uses reference: {$pathItem['$ref']}
";
            continue;
        }
        foreach ($pathItem as $method => $operation) {
            $methodStr = strtolower($method);
            if (!in_array($methodStr, ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'])) {
                continue; // Ignoring extensions or invalid methods
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
