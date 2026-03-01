<?php

declare(strict_types=1);

declare (strict_types=1);
namespace Cdd\Components;

/**
 * Compiles a Components Object from raw PHP definitions.
 *
 * @param array $schemas The schemas map
 * @param array $parameters The parameters map
 * @param array $responses The responses map
 * @return array The compiled components object
 */
function parse(array $schemas = [], array $parameters = [], array $responses = []): array
{
    $components = [];
    if (!empty($schemas)) {
        $components['schemas'] = $schemas;
    }
    if (!empty($parameters)) {
        $components['parameters'] = $parameters;
    }
    if (!empty($responses)) {
        $components['responses'] = $responses;
    }
    return $components;
}
/**
 * Validates a Components Object.
 */
function validateComponentsObject(mixed $components): void
{
    if (!is_array($components)) {
        throw new \RuntimeException('Field "components" must be an object');
    }
        $validKeys = [
        'schemas' => '\Cdd\Schemas\validateSchemaOrReferenceObject',
        'responses' => '\Cdd\Responses\validateResponseOrReferenceObject',
        'parameters' => '\Cdd\Parameters\validateParameterOrReferenceObject',
        'examples' => '\Cdd\Mocks\validateExampleOrReferenceObject',
        'requestBodies' => '\Cdd\RequestBodies\validateRequestBodyOrReferenceObject',
        'headers' => '\Cdd\Responses\validateHeaderOrReferenceObject',
        'securitySchemes' => '\Cdd\Security\validateSecuritySchemeOrReferenceObject',
        'links' => '\Cdd\Responses\validateLinkOrReferenceObject',
        'callbacks' => '\Cdd\Operations\validateCallbackOrReferenceObject',
        'pathItems' => '\Cdd\Paths\validatePathItemObject',
        'mediaTypes' => '\Cdd\Encoding\validateMediaTypeOrReferenceObject'
    ];
    foreach ($validKeys as $key => $validator) {
        if (isset($components[$key])) {
            if (!is_array($components[$key])) {
                throw new \RuntimeException("Components '{$key}' must be a map");
            }
            foreach ($components[$key] as $name => $item) {
                if (!preg_match('/^[a-zA-Z0-9\.\-_]+$/', $name)) {
                    throw new \RuntimeException("Components '{$key}' map keys must match ^[a-zA-Z0-9\\.\\-_]+\$");
                }
                if (function_exists($validator)) {
                    // Call the local validation function
                    $func = $validator;
                    $func($item);
                }
            }
        }
    }
}
