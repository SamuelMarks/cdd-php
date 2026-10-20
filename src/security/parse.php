<?php

/**
 * Parses and validates OpenAPI Security Scheme and Security Requirement Objects.
 *
 * @package Cdd\Security
 */

declare(strict_types=1);

namespace Cdd\Security;

/**
 * Parses security requirements mapping into OpenAPI Security Requirement Object array.
 *
 * @param array<string, array<int, string>> $requirements Map of security scheme names to scope lists.
 * @return array<int, array<string, array<int, string>>> List of OpenAPI Security Requirement Objects.
 */
function parse(array $requirements): array
{
    $security = [];
    foreach ($requirements as $name => $scopes) {
        $security[] = [$name => $scopes];
    }
    return $security;
}

/**
 * Validates a Security Scheme Object or Reference Object.
 *
 * @param mixed $scheme The Security Scheme or Reference Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateSecuritySchemeOrReferenceObject(mixed $scheme): void
{
    if (!is_array($scheme)) {
        throw \Cdd\Exceptions\CddException::validation('Security Scheme must be an object');
    }
    if (isset($scheme['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($scheme);
        return;
    }
    if (!isset($scheme['type']) || !is_string($scheme['type'])) {
        throw \Cdd\Exceptions\CddException::validation('Security Scheme must contain a "type" string');
    }
    $validTypes = ['apiKey', 'http', 'mutualTLS', 'oauth2', 'openIdConnect'];
    if (!in_array($scheme['type'], $validTypes, true)) {
        throw \Cdd\Exceptions\CddException::validation('Security Scheme "type" must be one of: apiKey, http, mutualTLS, oauth2, openIdConnect');
    }
    if (isset($scheme['description']) && !is_string($scheme['description'])) {
        throw \Cdd\Exceptions\CddException::validation('Security Scheme "description" must be a string');
    }
    if (isset($scheme['deprecated']) && !is_bool($scheme['deprecated'])) {
        throw \Cdd\Exceptions\CddException::validation('Security Scheme "deprecated" must be a boolean');
    }
    if ($scheme['type'] === 'apiKey') {
        if (!isset($scheme['name']) || !is_string($scheme['name'])) {
            throw \Cdd\Exceptions\CddException::validation('Security Scheme "apiKey" requires a "name" string');
        }
        if (!isset($scheme['in']) || !is_string($scheme['in']) || !in_array($scheme['in'], ['query', 'header', 'cookie'], true)) {
            throw \Cdd\Exceptions\CddException::validation('Security Scheme "apiKey" requires an "in" string (query, header, cookie)');
        }
    } elseif ($scheme['type'] === 'http') {
        if (!isset($scheme['scheme']) || !is_string($scheme['scheme'])) {
            throw \Cdd\Exceptions\CddException::validation('Security Scheme "http" requires a "scheme" string');
        }
        if (isset($scheme['bearerFormat']) && !is_string($scheme['bearerFormat'])) {
            throw \Cdd\Exceptions\CddException::validation('Security Scheme "bearerFormat" must be a string');
        }
    } elseif ($scheme['type'] === 'oauth2') {
        if (!isset($scheme['flows']) || !is_array($scheme['flows'])) {
            throw \Cdd\Exceptions\CddException::validation('Security Scheme "oauth2" requires a "flows" map');
        }
        if (isset($scheme['oauth2MetadataUrl']) && !is_string($scheme['oauth2MetadataUrl'])) {
            throw \Cdd\Exceptions\CddException::validation('Security Scheme "oauth2MetadataUrl" must be a string');
        }
        $validFlows = ['implicit', 'password', 'clientCredentials', 'authorizationCode', 'deviceAuthorization'];
        foreach ($scheme['flows'] as $flowType => $flowObj) {
            if (str_starts_with((string) $flowType, 'x-')) {
                continue;
            }
            if (!in_array($flowType, $validFlows, true)) {
                throw \Cdd\Exceptions\CddException::validation('OAuth2 flow type must be one of: implicit, password, clientCredentials, authorizationCode, deviceAuthorization');
            }
            if (!is_array($flowObj)) {
                throw \Cdd\Exceptions\CddException::validation('OAuth2 flow must be an object');
            }
            if (!isset($flowObj['scopes']) || !is_array($flowObj['scopes'])) {
                throw \Cdd\Exceptions\CddException::validation('OAuth2 flow must contain a "scopes" map');
            }
            if (in_array($flowType, ['implicit', 'authorizationCode'], true)) {
                if (!isset($flowObj['authorizationUrl']) || !is_string($flowObj['authorizationUrl'])) {
                    throw \Cdd\Exceptions\CddException::validation("OAuth2 {$flowType} flow requires an 'authorizationUrl' string");
                }
            }
            if (in_array($flowType, ['password', 'clientCredentials', 'authorizationCode', 'deviceAuthorization'], true)) {
                if (!isset($flowObj['tokenUrl']) || !is_string($flowObj['tokenUrl'])) {
                    throw \Cdd\Exceptions\CddException::validation("OAuth2 {$flowType} flow requires a 'tokenUrl' string");
                }
            }
            if ($flowType === 'deviceAuthorization') {
                if (!isset($flowObj['deviceAuthorizationUrl']) || !is_string($flowObj['deviceAuthorizationUrl'])) {
                    throw \Cdd\Exceptions\CddException::validation("OAuth2 deviceAuthorization flow requires a 'deviceAuthorizationUrl' string");
                }
            }
            if (isset($flowObj['refreshUrl']) && !is_string($flowObj['refreshUrl'])) {
                throw \Cdd\Exceptions\CddException::validation("OAuth2 flow 'refreshUrl' must be a string");
            }
        }
    } elseif ($scheme['type'] === 'openIdConnect') {
        if (!isset($scheme['openIdConnectUrl']) || !is_string($scheme['openIdConnectUrl'])) {
            throw \Cdd\Exceptions\CddException::validation('Security Scheme "openIdConnect" requires an "openIdConnectUrl" string');
        }
    }
}

/**
 * Validates a Security Requirement Object.
 *
 * @param mixed $security The Security Requirement Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateSecurityRequirementObject(mixed $security): void
{
    if (!is_array($security)) {
        throw \Cdd\Exceptions\CddException::validation('Security Requirement must be an object/map');
    }
    foreach ($security as $scopes) {
        if (!is_array($scopes)) {
            throw \Cdd\Exceptions\CddException::validation('Security Requirement scopes must be an array of strings');
        }
        foreach ($scopes as $scope) {
            if (!is_string($scope)) {
                throw \Cdd\Exceptions\CddException::validation('Security Requirement scopes must be an array of strings');
            }
        }
    }
}
