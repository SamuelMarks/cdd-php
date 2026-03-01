<?php

declare(strict_types=1);

declare (strict_types=1);
namespace Cdd\Security;

/**
 * Parses security requirements mapping into OpenAPI Security Requirement Object array.
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
 */
function validateSecuritySchemeOrReferenceObject(mixed $scheme): void
{
    if (!is_array($scheme)) {
        throw new \RuntimeException('Security Scheme must be an object');
    }
    if (isset($scheme['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($scheme);
        return;
    }
    if (!isset($scheme['type']) || !is_string($scheme['type'])) {
        throw new \RuntimeException('Security Scheme must contain a "type" string');
    }
    $validTypes = ['apiKey', 'http', 'mutualTLS', 'oauth2', 'openIdConnect'];
    if (!in_array($scheme['type'], $validTypes, true)) {
        throw new \RuntimeException('Security Scheme "type" must be one of: apiKey, http, mutualTLS, oauth2, openIdConnect');
    }
    if (isset($scheme['description']) && !is_string($scheme['description'])) {
        throw new \RuntimeException('Security Scheme "description" must be a string');
    }
    if (isset($scheme['deprecated']) && !is_bool($scheme['deprecated'])) {
        throw new \RuntimeException('Security Scheme "deprecated" must be a boolean');
    }
    switch ($scheme['type']) {
        case 'apiKey':
            if (!isset($scheme['name']) || !is_string($scheme['name'])) {
                throw new \RuntimeException('Security Scheme "apiKey" requires a "name" string');
            }
            if (!isset($scheme['in']) || !is_string($scheme['in']) || !in_array($scheme['in'], ['query', 'header', 'cookie'], true)) {
                throw new \RuntimeException('Security Scheme "apiKey" requires an "in" string (query, header, cookie)');
            }
            break;
        case 'http':
            if (!isset($scheme['scheme']) || !is_string($scheme['scheme'])) {
                throw new \RuntimeException('Security Scheme "http" requires a "scheme" string');
            }
            if (isset($scheme['bearerFormat']) && !is_string($scheme['bearerFormat'])) {
                throw new \RuntimeException('Security Scheme "bearerFormat" must be a string');
            }
            break;
        case 'oauth2':
            if (!isset($scheme['flows']) || !is_array($scheme['flows'])) {
                throw new \RuntimeException('Security Scheme "oauth2" requires a "flows" map');
            }
            if (isset($scheme['oauth2MetadataUrl']) && !is_string($scheme['oauth2MetadataUrl'])) {
                throw new \RuntimeException('Security Scheme "oauth2MetadataUrl" must be a string');
            }
            $validFlows = ['implicit', 'password', 'clientCredentials', 'authorizationCode', 'deviceAuthorization'];
            foreach ($scheme['flows'] as $flowType => $flowObj) {
                if (str_starts_with($flowType, 'x-')) {
                    continue;
                }
                if (!in_array($flowType, $validFlows, true)) {
                    throw new \RuntimeException('OAuth2 flow type must be one of: implicit, password, clientCredentials, authorizationCode, deviceAuthorization');
                }
                if (!is_array($flowObj)) {
                    throw new \RuntimeException('OAuth2 flow must be an object');
                }
                if (!isset($flowObj['scopes']) || !is_array($flowObj['scopes'])) {
                    throw new \RuntimeException('OAuth2 flow must contain a "scopes" map');
                }
                if (in_array($flowType, ['implicit', 'authorizationCode'])) {
                    if (!isset($flowObj['authorizationUrl']) || !is_string($flowObj['authorizationUrl'])) {
                        throw new \RuntimeException("OAuth2 {$flowType} flow requires an 'authorizationUrl' string");
                    }
                }
                if (in_array($flowType, ['password', 'clientCredentials', 'authorizationCode', 'deviceAuthorization'])) {
                    if (!isset($flowObj['tokenUrl']) || !is_string($flowObj['tokenUrl'])) {
                        throw new \RuntimeException("OAuth2 {$flowType} flow requires a 'tokenUrl' string");
                    }
                }
                if ($flowType === 'deviceAuthorization') {
                    if (!isset($flowObj['deviceAuthorizationUrl']) || !is_string($flowObj['deviceAuthorizationUrl'])) {
                        throw new \RuntimeException("OAuth2 deviceAuthorization flow requires a 'deviceAuthorizationUrl' string");
                    }
                }
                if (isset($flowObj['refreshUrl']) && !is_string($flowObj['refreshUrl'])) {
                    throw new \RuntimeException("OAuth2 flow 'refreshUrl' must be a string");
                }
            }
            break;
        case 'openIdConnect':
            if (!isset($scheme['openIdConnectUrl']) || !is_string($scheme['openIdConnectUrl'])) {
                throw new \RuntimeException('Security Scheme "openIdConnect" requires an "openIdConnectUrl" string');
            }
            break;
    }
}
/**
 * Validates a Security Requirement Object.
 */
function validateSecurityRequirementObject(mixed $security): void
{
    if (!is_array($security)) {
        throw new \RuntimeException('Security Requirement must be an object/map');
    }
    foreach ($security as $name => $scopes) {
        if (!is_array($scopes)) {
            throw new \RuntimeException('Security Requirement scopes must be an array of strings');
        }
        foreach ($scopes as $scope) {
            if (!is_string($scope)) {
                throw new \RuntimeException('Security Requirement scopes must be an array of strings');
            }
        }
    }
}
