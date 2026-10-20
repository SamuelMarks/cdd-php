<?php

/**
 * Parses and validates OpenAPI 3.0.x, 3.1.x, 3.2.0, and Swagger 2.0 JSON documents.
 *
 * @package Cdd\Openapi
 */

declare(strict_types=1);

namespace Cdd\Openapi;

use Cdd\Exceptions\CddException;

/**
 * Parses an OpenAPI JSON string into a PHP array structure.
 *
 * @param string $json The raw OpenAPI or Swagger JSON string.
 * @return array<string, mixed> The validated OpenAPI 3.2.0 array structure.
 * @throws CddException If JSON decoding or OpenAPI validation fails.
 */
function parse(string $json): array
{
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw CddException::jsonDecode('Invalid JSON provided: ' . json_last_error_msg());
    }
    if (!is_array($data)) {
        throw CddException::validation('OpenAPI document must be a JSON object');
    }
    if (isset($data['swagger']) && !isset($data['openapi'])) {
        $data = upgrade_swagger2_to_openapi3($data);
    }

    if (!isset($data['openapi'])) {
        throw CddException::validation('Missing REQUIRED field "openapi" in OpenAPI Object');
    }
    if (!is_string($data['openapi']) || (!str_starts_with($data['openapi'], '3.0.') && !str_starts_with($data['openapi'], '3.1.') && $data['openapi'] !== '3.2.0')) {
        throw CddException::validation('Spec must be OpenAPI 3.0.x, 3.1.x, or 3.2.0');
    }
    $data['openapi'] = '3.2.0';

    if (isset($data['$self']) && !is_string($data['$self'])) {
        throw CddException::validation('Field "$self" must be a string (URI reference)');
    }
    if (!isset($data['info'])) {
        throw CddException::validation('Missing REQUIRED field "info" in OpenAPI Object');
    }
    \Cdd\Info\validateInfoObject($data['info']);

    if (isset($data['jsonSchemaDialect']) && !is_string($data['jsonSchemaDialect'])) {
        throw CddException::validation('Field "jsonSchemaDialect" must be a string (URI)');
    }
    if (isset($data['servers'])) {
        if (!is_array($data['servers'])) {
            throw CddException::validation('Field "servers" must be an array of Server Objects');
        }
        foreach ($data['servers'] as $server) {
            \Cdd\Servers\validateServerObject($server);
        }
    }

    $hasPaths = isset($data['paths']) && is_array($data['paths']);
    $hasComponents = isset($data['components']) && is_array($data['components']);
    $hasWebhooks = false;
    if (isset($data['webhooks'])) {
        if (!is_array($data['webhooks'])) {
            throw CddException::validation('Field "webhooks" must be a map');
        }
        $hasWebhooks = true;
        foreach ($data['webhooks'] as $pathItem) {
            \Cdd\Paths\validatePathItemObject($pathItem);
        }
    }
    if (isset($data['paths'])) {
        \Cdd\Paths\validatePathsObject($data['paths']);
    }
    if (isset($data['components'])) {
        \Cdd\Components\validateComponentsObject($data['components']);
    }
    if (!$hasPaths && !$hasComponents && !$hasWebhooks) {
        throw CddException::validation('Spec must contain paths, components, or webhooks');
    }

    if (isset($data['security'])) {
        if (!is_array($data['security'])) {
            throw CddException::validation('Field "security" must be an array of Security Requirement Objects');
        }
        foreach ($data['security'] as $secReq) {
            \Cdd\Security\validateSecurityRequirementObject($secReq);
        }
    }
    if (isset($data['tags'])) {
        if (!is_array($data['tags'])) {
            throw CddException::validation('Field "tags" must be an array of Tag Objects');
        }
        foreach ($data['tags'] as $tag) {
            \Cdd\Info\validateTagObject($tag);
        }
    }
    if (isset($data['externalDocs'])) {
        \Cdd\Info\validateExternalDocsObject($data['externalDocs']);
    }
    return $data;
}

/**
 * Validates a Reference Object.
 *
 * @param array<string, mixed> $ref The Reference Object to validate.
 * @return void
 * @throws CddException If validation fails.
 */
function validateReferenceObject(array $ref): void
{
    if (!is_string($ref['$ref'])) {
        throw CddException::validation('Reference "$ref" must be a string');
    }
    if (isset($ref['summary']) && !is_string($ref['summary'])) {
        throw CddException::validation('Reference "summary" must be a string');
    }
    if (isset($ref['description']) && !is_string($ref['description'])) {
        throw CddException::validation('Reference "description" must be a string');
    }
}
