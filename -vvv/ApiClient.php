<?php

class ApiClient {
    private $baseUrl;

    public function __construct(string $baseUrl) {
        $this->baseUrl = $baseUrl;
    }

    protected function requireSecurity(string $name, array $scopes = []) {
        // Base security requirement mock
    }

}
