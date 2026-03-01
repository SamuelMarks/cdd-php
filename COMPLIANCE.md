# Compliance

The `cdd-php` project strictly adheres to the OpenAPI Specification (OAS). We currently aim for **OpenAPI 3.2.0** compliance.

## OpenAPI 3.2.0 Compliance

`cdd-php` targets the full OpenAPI 3.2.0 spec.

Currently implemented:
- Basic models and nested object parsing/emitting.
- Server paths, operations, request bodies, and responses.
- Security Schemas (HTTP, API Key, OAuth2, OpenID Connect).
- Media Type objects, including `itemSchema` and `itemEncoding` tags.
- Links and Callbacks placeholders.

Any divergence or lack of feature support is either on the immediate roadmap or unsupported by native PHP paradigms.

We maintain a suite of tests tracking OAS 3.2.0 capabilities, guaranteeing `100%` compliance locally for the implemented features.
