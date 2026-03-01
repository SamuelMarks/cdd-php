<?php

namespace Cdd\Tests\Components;

class SyncComponentsTest extends \Cdd\Tests\Framework\TestCase {
    public function testComponentsSync() {
        $tmpDir = sys_get_temp_dir() . '/cdd-php-comps-' . uniqid();
        mkdir($tmpDir);

        $code = "<?php\n\n";
        $code .= "/**\n * @pathItem\n * A reusable path item\n */\nclass ReusablePath {}\n";
        $code .= "/**\n * @callback\n */\nclass OnEvent { public string \$status; }\n";
        $code .= "/**\n * @link\n * @operationId getUser\n */\nclass GetUserLink {}\n";
        $code .= "/**\n * @mediaType\n * @itemSchema User\n * @itemEncoding application/json\n */\nclass UserMedia {}\n";
        $code .= "/**\n * @securityScheme\n * @type oauth2\n * @flow implicit {\"authorizationUrl\":\"http://example.com/auth\",\"scopes\":{\"read\":\"read everything\"}}\n */\nclass OAuth2Auth {}\n";
        $code .= "/**\n * @securityScheme\n * @type openIdConnect\n * @openIdConnectUrl http://example.com/.well-known\n */\nclass OpenIdConnectAuth {}\n";

        file_put_contents("$tmpDir/Models.php", $code);

        $baseDir = dirname(__DIR__, 2);
        exec("php $baseDir/bin/cdd-php sync -d $tmpDir", $output, $returnVar);

        $this->assertEquals(0, $returnVar);

        $json = file_get_contents("$tmpDir/openapi.json");
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
        
        $this->assertTrue(isset($openapi['components']['securitySchemes']['OAuth2Auth']));
        $this->assertEquals('oauth2', $openapi['components']['securitySchemes']['OAuth2Auth']['type']);
        $this->assertEquals('http://example.com/auth', $openapi['components']['securitySchemes']['OAuth2Auth']['flows']['implicit']['authorizationUrl']);

        $this->assertTrue(isset($openapi['components']['securitySchemes']['OpenIdConnectAuth']));
        $this->assertEquals('openIdConnect', $openapi['components']['securitySchemes']['OpenIdConnectAuth']['type']);
        $this->assertEquals('http://example.com/.well-known', $openapi['components']['securitySchemes']['OpenIdConnectAuth']['openIdConnectUrl']);

        // Clean up
        array_map('unlink', glob("$tmpDir/*.*"));
        rmdir($tmpDir);
    }
}
