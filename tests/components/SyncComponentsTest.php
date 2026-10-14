<?php

/**
 * Tests for synchronizing all OpenAPI Component types from annotated PHP classes.
 *
 * @package Cdd\Tests\Components
 */

declare(strict_types=1);

namespace Cdd\Tests\Components;

use Cdd\Cli\CddCli;
use Cdd\Tests\Framework\TestCase;

/**
 * Tests `CddCli::run(['cdd-php', 'sync', ...])` for component docblock tags.
 */
class SyncComponentsTest extends TestCase
{
    /**
     * Tests syncing pathItems, callbacks, links, mediaTypes, parameters, responses, requestBodies, headers, and securitySchemes.
     *
     * @return void
     */
    public function testComponentsSync(): void
    {
        $tmpDir = sys_get_temp_dir() . '/cdd-php-comps-' . uniqid();
        mkdir($tmpDir);

        $code = "<?php

";
        $code .= "/**
 * @pathItem
 * A reusable path item
 */
class ReusablePath {}
";
        $code .= "/**
 * @callback
 */
class OnEvent { public string \$status; }
";
        $code .= "/**
 * @link
 * @operationId getUser
 */
class GetUserLink {}
";
        $code .= "/**
 * @mediaType
 * @itemSchema User
 * @itemEncoding application/json
 */
class UserMedia {}
";
        $code .= "/**\n * @mediaType\n * @itemEncoding " . '{"contentType":"application/xml","style":"form"}' . "\n */\nclass XmlMedia {}\n";
        $code .= "/**
 * @parameter
 * @in header
 * @name X-Custom
 * @required true
 */
class CustomParam { public string \$val; }
";
        $code .= "/**
 * @response
 * A custom response
 */
class CustomResponse { public string \$msg; }
";
        $code .= "/**
 * @requestBody
 * A custom request body
 */
class CustomBody { public string \$bodyVal; }
";
        $code .= "/**
 * @header
 * A custom header
 */
class CustomHeader { public string \$hVal; }
";
        $code .= "/**
 * @securityScheme
 * @type http
 * @scheme bearer
 * @bearerFormat JWT
 */
class HttpAuth {}
";
        $code .= "/**
 * @securityScheme
 * @type apiKey
 * @in query
 * @name api_key
 */
class ApiKeyAuth {}
";
        $code .= "/**\n * @securityScheme\n * @type oauth2\n * @flow implicit " . '{"authorizationUrl":"http://example.com/auth","scopes":{"read":"read everything"}}' . "\n */\nclass OAuth2Auth {}\n";
        $code .= "/**
 * @securityScheme
 * @type openIdConnect
 * @openIdConnectUrl http://example.com/.well-known
 */
class OpenIdConnectAuth {}
";

        mkdir("$tmpDir/src", 0777, true);
        file_put_contents("$tmpDir/src/Models.php", $code);

        ob_start();
        $returnVar = CddCli::run(['cdd-php', 'sync', '-i', $tmpDir]);
        ob_end_clean();

        $this->assertEquals(0, $returnVar);

        $json = (string) file_get_contents("$tmpDir/openapi.json");
        $openapi = json_decode($json, true);

        $this->assertTrue(isset($openapi['components']['pathItems']['ReusablePath']));
        $this->assertEquals('A reusable path item', $openapi['components']['pathItems']['ReusablePath']['description']);

        $this->assertTrue(isset($openapi['components']['callbacks']['OnEvent']));
        $this->assertTrue(isset($openapi['components']['callbacks']['OnEvent']['{$request.query.callbackUrl}']['post']['requestBody']['content']['application/json']['schema']['properties']['status']));

        $this->assertTrue(isset($openapi['components']['links']['GetUserLink']));
        $this->assertEquals('getUser', $openapi['components']['links']['GetUserLink']['operationId']);

        $this->assertTrue(isset($openapi['components']['mediaTypes']['UserMedia']));
        $this->assertEquals('#/components/schemas/User', $openapi['components']['mediaTypes']['UserMedia']['itemSchema']['$ref']);
        $this->assertEquals('application/json', $openapi['components']['mediaTypes']['UserMedia']['itemEncoding']['contentType']);

        $this->assertEquals('X-Custom', $openapi['components']['parameters']['CustomParam']['name']);
        $this->assertEquals('header', $openapi['components']['parameters']['CustomParam']['in']);
        $this->assertTrue($openapi['components']['parameters']['CustomParam']['required']);

        $this->assertEquals('A custom response', $openapi['components']['responses']['CustomResponse']['description']);
        $this->assertEquals('A custom request body', $openapi['components']['requestBodies']['CustomBody']['description']);
        $this->assertEquals('A custom header', $openapi['components']['headers']['CustomHeader']['description']);

        $this->assertEquals('http', $openapi['components']['securitySchemes']['HttpAuth']['type']);
        $this->assertEquals('bearer', $openapi['components']['securitySchemes']['HttpAuth']['scheme']);
        $this->assertEquals('JWT', $openapi['components']['securitySchemes']['HttpAuth']['bearerFormat']);

        $this->assertEquals('apiKey', $openapi['components']['securitySchemes']['ApiKeyAuth']['type']);
        $this->assertEquals('query', $openapi['components']['securitySchemes']['ApiKeyAuth']['in']);
        $this->assertEquals('api_key', $openapi['components']['securitySchemes']['ApiKeyAuth']['name']);

        $this->assertTrue(isset($openapi['components']['securitySchemes']['OAuth2Auth']));
        $this->assertEquals('oauth2', $openapi['components']['securitySchemes']['OAuth2Auth']['type']);
        $this->assertEquals('http://example.com/auth', $openapi['components']['securitySchemes']['OAuth2Auth']['flows']['implicit']['authorizationUrl']);

        $this->assertTrue(isset($openapi['components']['securitySchemes']['OpenIdConnectAuth']));
        $this->assertEquals('openIdConnect', $openapi['components']['securitySchemes']['OpenIdConnectAuth']['type']);
        $this->assertEquals('http://example.com/.well-known', $openapi['components']['securitySchemes']['OpenIdConnectAuth']['openIdConnectUrl']);

        $this->removeDir($tmpDir);
    }
}
