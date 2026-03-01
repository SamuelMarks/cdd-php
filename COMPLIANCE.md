# OpenAPI 3.2.0 Compliance

The `cdd-php` parser and emitter strive for full compliance with the OpenAPI 3.2.0 specification. 

## Supported Features
- **Paths and Operations**: Fully supported including parameters, request bodies, and responses.
- **Components**: Schemas, Responses, Parameters, Examples, RequestBodies, Headers, SecuritySchemes, Links, Callbacks, PathItems.
- **Webhooks**: Supported.
- **Servers**: Supported.
- **Metadata**: Info, Tags, ExternalDocs, jsonSchemaDialect.

## Ongoing Work
- Full verification of all complex `anyOf`, `oneOf`, and `allOf` combinations.
- Comprehensive mapping of XML object serialization.
- Callback and Link complex evaluations.

We iteratively parse and emit until full compliance is achieved. You can view our coverage in our test suites.