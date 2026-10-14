<?php

/**
 * Tests for OpenAPI 3.2.0 Security Scheme features (`deviceAuthorization`, `mutualTLS`, `oauth2MetadataUrl`).
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests parsing and validating OpenAPI 3.2.0 Security Scheme extensions.
 */
class ParseEmitSecurityTest extends TestCase
{
    /**
     * Tests parsing a valid OAuth2 `deviceAuthorization` flow.
     *
     * @return void
     */
    public function testParseSecurityDeviceAuthorization(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": { "title": "Test", "version": "1.0" },
            "paths": {},
            "components": {
                "securitySchemes": {
                    "oauth2": {
                        "type": "oauth2",
                        "flows": {
                            "deviceAuthorization": {
                                "deviceAuthorizationUrl": "https://example.com/device",
                                "tokenUrl": "https://example.com/token",
                                "scopes": {
                                    "read": "read stuff"
                                }
                            }
                        }
                    }
                }
            }
        }';
        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals('https://example.com/device', $parsed['components']['securitySchemes']['oauth2']['flows']['deviceAuthorization']['deviceAuthorizationUrl']);
    }

    /**
     * Tests error when OAuth2 `deviceAuthorization` flow is missing `deviceAuthorizationUrl`.
     *
     * @return void
     */
    public function testParseSecurityDeviceAuthorizationInvalid(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": { "title": "Test", "version": "1.0" },
            "paths": {},
            "components": {
                "securitySchemes": {
                    "oauth2": {
                        "type": "oauth2",
                        "flows": {
                            "deviceAuthorization": {
                                "tokenUrl": "https://example.com/token",
                                "scopes": {}
                            }
                        }
                    }
                }
            }
        }';
        $this->assertThrows(function () use ($json): void {
            \Cdd\Openapi\parse($json);
        }, \RuntimeException::class, "OAuth2 deviceAuthorization flow requires a 'deviceAuthorizationUrl' string");
    }

    /**
     * Tests parsing a `mutualTLS` Security Scheme.
     *
     * @return void
     */
    public function testParseSecurityMutualTLS(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": { "title": "Test", "version": "1.0" },
            "paths": {},
            "components": {
                "securitySchemes": {
                    "mtls": {
                        "type": "mutualTLS"
                    }
                }
            }
        }';
        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals('mutualTLS', $parsed['components']['securitySchemes']['mtls']['type']);
    }

    /**
     * Tests parsing `oauth2MetadataUrl` on an OAuth2 Security Scheme.
     *
     * @return void
     */
    public function testParseSecurityOAuth2Metadata(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": { "title": "Test", "version": "1.0" },
            "paths": {},
            "components": {
                "securitySchemes": {
                    "oauth2": {
                        "type": "oauth2",
                        "oauth2MetadataUrl": "https://example.com/meta",
                        "flows": {
                            "clientCredentials": {
                                "tokenUrl": "https://example.com/token",
                                "scopes": {}
                            }
                        }
                    }
                }
            }
        }';
        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals('https://example.com/meta', $parsed['components']['securitySchemes']['oauth2']['oauth2MetadataUrl']);
    }
}
