<?php

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

class ParseEmitSecurityTest extends TestCase {
    public function testParseSecurityDeviceAuthorization() {
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
        $this->assertEquals("https://example.com/device", $parsed['components']['securitySchemes']['oauth2']['flows']['deviceAuthorization']['deviceAuthorizationUrl']);
    }

    public function testParseSecurityDeviceAuthorizationInvalid() {
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
        try {
            \Cdd\Openapi\parse($json);
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), "OAuth2 deviceAuthorization flow requires a 'deviceAuthorizationUrl' string") !== false);
        }
    }

    public function testParseSecurityMutualTLS() {
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
        $this->assertEquals("mutualTLS", $parsed['components']['securitySchemes']['mtls']['type']);
    }

    public function testParseSecurityOAuth2Metadata() {
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
        $this->assertEquals("https://example.com/meta", $parsed['components']['securitySchemes']['oauth2']['oauth2MetadataUrl']);
    }
}
