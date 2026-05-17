<?php

declare(strict_types=1);

namespace Cdd\Tests\Security;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testParseAndEmit()
    {
        $sec = \Cdd\Security\parse(['api_key' => [], 'oauth' => ['read', 'write']]);
        $this->assertEquals(2, count($sec));
        $this->assertTrue(isset($sec[0]['api_key']));
        $this->assertEquals('read', $sec[1]['oauth'][0]);

        $emitted = \Cdd\Security\emit($sec);
        $this->assertTrue(strpos($emitted, "requireSecurity('api_key', [], \$headers, \$params)") !== false);
        $this->assertTrue(strpos($emitted, "requireSecurity('oauth', ['read', 'write'], \$headers, \$params)") !== false);
    }

    public function testValidateSecurityRequirementObjectErrors()
    {
        $caught = false;
        try {
            \Cdd\Security\validateSecurityRequirementObject('string');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Security Requirement must be an object/map', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);
    }

    public function testValidateSecurityRequirementObjectScopesError()
    {
        $caught = false;
        try {
            \Cdd\Security\validateSecurityRequirementObject(['api_key' => 'not_array']);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Security Requirement scopes must be an array of strings', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);
    }

    public function testValidateSecurityRequirementObjectScopeElementsError()
    {
        $caught = false;
        try {
            \Cdd\Security\validateSecurityRequirementObject(['oauth' => [123]]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Security Requirement scopes must be an array of strings', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);
    }

    public function testValidateSecuritySchemeOrReferenceObjectErrors()
    {
        $tests = [
            ['input' => 'string', 'error' => 'Security Scheme must be an object'],
            ['input' => ['type' => 123], 'error' => 'Security Scheme must contain a "type" string'],
            ['input' => ['type' => 'invalid'], 'error' => 'Security Scheme "type" must be one of: apiKey, http, mutualTLS, oauth2, openIdConnect'],
            ['input' => ['type' => 'apiKey', 'description' => 123], 'error' => 'Security Scheme "description" must be a string'],
            ['input' => ['type' => 'apiKey', 'deprecated' => 'yes'], 'error' => 'Security Scheme "deprecated" must be a boolean'],
            
            ['input' => ['type' => 'apiKey'], 'error' => 'Security Scheme "apiKey" requires a "name" string'],
            ['input' => ['type' => 'apiKey', 'name' => 'api_key'], 'error' => 'Security Scheme "apiKey" requires an "in" string (query, header, cookie)'],
            
            ['input' => ['type' => 'http'], 'error' => 'Security Scheme "http" requires a "scheme" string'],
            ['input' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 123], 'error' => 'Security Scheme "bearerFormat" must be a string'],
            
            ['input' => ['type' => 'oauth2'], 'error' => 'Security Scheme "oauth2" requires a "flows" map'],
            ['input' => ['type' => 'oauth2', 'flows' => [], 'oauth2MetadataUrl' => 123], 'error' => 'Security Scheme "oauth2MetadataUrl" must be a string'],
            
            ['input' => ['type' => 'oauth2', 'flows' => ['invalid' => []]], 'error' => 'OAuth2 flow type must be one of: implicit, password, clientCredentials, authorizationCode, deviceAuthorization'],
            ['input' => ['type' => 'oauth2', 'flows' => ['implicit' => 'not_array']], 'error' => 'OAuth2 flow must be an object'],
            ['input' => ['type' => 'oauth2', 'flows' => ['implicit' => []]], 'error' => 'OAuth2 flow must contain a "scopes" map'],
            ['input' => ['type' => 'oauth2', 'flows' => ['implicit' => ['scopes' => []]]], 'error' => "OAuth2 implicit flow requires an 'authorizationUrl' string"],
            ['input' => ['type' => 'oauth2', 'flows' => ['password' => ['scopes' => []]]], 'error' => "OAuth2 password flow requires a 'tokenUrl' string"],
            ['input' => ['type' => 'oauth2', 'flows' => ['deviceAuthorization' => ['scopes' => [], 'tokenUrl' => 'foo']]], 'error' => "OAuth2 deviceAuthorization flow requires a 'deviceAuthorizationUrl' string"],
            ['input' => ['type' => 'oauth2', 'flows' => ['password' => ['scopes' => [], 'tokenUrl' => 'foo', 'refreshUrl' => 123]]], 'error' => "OAuth2 flow 'refreshUrl' must be a string"],

            ['input' => ['type' => 'openIdConnect'], 'error' => 'Security Scheme "openIdConnect" requires an "openIdConnectUrl" string'],
        ];

        foreach ($tests as $test) {
            $caught = false;
            try {
                \Cdd\Security\validateSecuritySchemeOrReferenceObject($test['input']);
            } catch (\RuntimeException $e) {
                $this->assertEquals($test['error'], $e->getMessage());
                $caught = true;
            }
            $this->assertTrue($caught, "Expected exception: {$test['error']}");
        }

        // Test success cases including reference
        \Cdd\Security\validateSecuritySchemeOrReferenceObject(['$ref' => '#/components/securitySchemes/ApiKeyAuth']);
        \Cdd\Security\validateSecuritySchemeOrReferenceObject(['type' => 'apiKey', 'name' => 'api_key', 'in' => 'header']);
        \Cdd\Security\validateSecuritySchemeOrReferenceObject(['type' => 'http', 'scheme' => 'bearer']);
        \Cdd\Security\validateSecuritySchemeOrReferenceObject(['type' => 'oauth2', 'flows' => ['x-custom' => 'ignore', 'implicit' => ['scopes' => [], 'authorizationUrl' => 'url']]]);
        \Cdd\Security\validateSecuritySchemeOrReferenceObject(['type' => 'openIdConnect', 'openIdConnectUrl' => 'url']);
        \Cdd\Security\validateSecuritySchemeOrReferenceObject(['type' => 'mutualTLS']);
    }
}
