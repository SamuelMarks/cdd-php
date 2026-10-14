<?php

declare(strict_types=1);

namespace Cdd\Tests\Components;

use Cdd\Cli\CddCli;

class SyncComponentsTest extends \Cdd\Tests\Framework\TestCase
{
    public function testComponentsSync()
    {
        $tmpDir = sys_get_temp_dir() . '/cdd-php-comps-' . uniqid();
        mkdir($tmpDir);

        $code = "<?php\n\n";
        $code .= "/**\n * @pathItem\n * A reusable path item\n */\nclass ReusablePath {}\n";
        $code .= "/**\n * @callback\n */\nclass OnEvent { public string \$status; }\n";
        $code .= "/**\n * @link\n * @operationId getUser\n */\nclass GetUserLink {}\n";
        $code .= "/**\n * @mediaType\n * @itemSchema User\n * @itemEncoding application/json\n */\nclass UserMedia {}\n";
        $code .= "/**\n * @parameter\n * @in header\n * @name X-Custom\n * @required true\n */\nclass CustomParam { public string \$val; }\n";
        $code .= "/**\n * @response\n * A custom response\n */\nclass CustomResponse { public string \$msg; }\n";
        $code .= "/**\n * @requestBody\n * A custom request body\n */\nclass CustomBody { public string \$bodyVal; }\n";
        $code .= "/**\n * @header\n * A custom header\n */\nclass CustomHeader { public string \$hVal; }\n";
        $code .= "/**\n * @securityScheme\n * @type http\n * @scheme bearer\n * @bearerFormat JWT\n */\nclass HttpAuth {}\n";
        $code .= "/**\n * @securityScheme\n * @type apiKey\n * @in query\n * @name api_key\n */\nclass ApiKeyAuth {}\n";
        $code .= "/**\n * @securityScheme\n * @type oauth2\n * @flow implicit {\"authorizationUrl\":\"http://example.com/auth\",\"scopes\":{\"read\":\"read everything\"}}\n */\nclass OAuth2Auth {}\n";
        $code .= "/**\n * @securityScheme\n * @type openIdConnect\n * @openIdConnectUrl http://example.com/.well-known\n */\nclass OpenIdConnectAuth {}\n";

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

        exec("rm -rf " . escapeshellarg($tmpDir));
    }
}
