<?php

class ApiClient
{
    private $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    protected function requireSecurity(string $name, array $scopes = [])
    {
        // Base security requirement mock
    }

    public function updatePet(array $params = [], array $body = [])
    {
        // Security Requirements
        $this->requireSecurity('petstore_auth', ['write:pets', 'read:pets']);
        $ch = curl_init();
        $url = "{$this->baseUrl}/pet";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('put'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function addPet(array $params = [], array $body = [])
    {
        // Security Requirements
        $this->requireSecurity('petstore_auth', ['write:pets', 'read:pets']);
        $ch = curl_init();
        $url = "{$this->baseUrl}/pet";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('post'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function findPetsByStatus(array $params = [], array $body = [])
    {
        // Security Requirements
        $this->requireSecurity('petstore_auth', ['write:pets', 'read:pets']);
        $ch = curl_init();
        $url = "{$this->baseUrl}/pet/findByStatus";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function findPetsByTags(array $params = [], array $body = [])
    {
        // Security Requirements
        $this->requireSecurity('petstore_auth', ['write:pets', 'read:pets']);
        $ch = curl_init();
        $url = "{$this->baseUrl}/pet/findByTags";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function getPetById(array $params = [], array $body = [])
    {
        // Security Requirements
        $this->requireSecurity('api_key', []);
        $this->requireSecurity('petstore_auth', ['write:pets', 'read:pets']);
        $ch = curl_init();
        $url = "{$this->baseUrl}/pet/{petId}";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function updatePetWithForm(array $params = [], array $body = [])
    {
        // Security Requirements
        $this->requireSecurity('petstore_auth', ['write:pets', 'read:pets']);
        $ch = curl_init();
        $url = "{$this->baseUrl}/pet/{petId}";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('post'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function deletePet(array $params = [], array $body = [])
    {
        // Security Requirements
        $this->requireSecurity('petstore_auth', ['write:pets', 'read:pets']);
        $ch = curl_init();
        $url = "{$this->baseUrl}/pet/{petId}";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('delete'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function uploadFile(array $params = [], array $body = [])
    {
        // Security Requirements
        $this->requireSecurity('petstore_auth', ['write:pets', 'read:pets']);
        $ch = curl_init();
        $url = "{$this->baseUrl}/pet/{petId}/uploadImage";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('post'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function getInventory(array $params = [], array $body = [])
    {
        // Security Requirements
        $this->requireSecurity('api_key', []);
        $ch = curl_init();
        $url = "{$this->baseUrl}/store/inventory";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function placeOrder(array $params = [], array $body = [])
    {
        $ch = curl_init();
        $url = "{$this->baseUrl}/store/order";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('post'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function getOrderById(array $params = [], array $body = [])
    {
        $ch = curl_init();
        $url = "{$this->baseUrl}/store/order/{orderId}";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function deleteOrder(array $params = [], array $body = [])
    {
        $ch = curl_init();
        $url = "{$this->baseUrl}/store/order/{orderId}";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('delete'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function createUser(array $params = [], array $body = [])
    {
        $ch = curl_init();
        $url = "{$this->baseUrl}/user";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('post'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function createUsersWithListInput(array $params = [], array $body = [])
    {
        $ch = curl_init();
        $url = "{$this->baseUrl}/user/createWithList";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('post'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function loginUser(array $params = [], array $body = [])
    {
        $ch = curl_init();
        $url = "{$this->baseUrl}/user/login";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function logoutUser(array $params = [], array $body = [])
    {
        $ch = curl_init();
        $url = "{$this->baseUrl}/user/logout";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function getUserByName(array $params = [], array $body = [])
    {
        $ch = curl_init();
        $url = "{$this->baseUrl}/user/{username}";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function updateUser(array $params = [], array $body = [])
    {
        $ch = curl_init();
        $url = "{$this->baseUrl}/user/{username}";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('put'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

    public function deleteUser(array $params = [], array $body = [])
    {
        $ch = curl_init();
        $url = "{$this->baseUrl}/user/{username}";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('delete'));
        $headers = [];
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('cURL Error: ' . $error);
        }
        return json_decode($response, true);
    }

}
