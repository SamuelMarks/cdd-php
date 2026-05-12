<?php

// Auto-generated tests

    public function testupdatePet() {
        $response = $this->call('put', '/pet');
        $this->assertEquals(200, $response->status());
    }

    public function testaddPet() {
        $response = $this->call('post', '/pet');
        $this->assertEquals(200, $response->status());
    }

    public function testfindPetsByStatus() {
        $response = $this->call('get', '/pet/findByStatus');
        $this->assertEquals(200, $response->status());
    }

    public function testfindPetsByTags() {
        $response = $this->call('get', '/pet/findByTags');
        $this->assertEquals(200, $response->status());
    }

    public function testgetPetById() {
        $response = $this->call('get', '/pet/{petId}');
        $this->assertEquals(200, $response->status());
    }

    public function testupdatePetWithForm() {
        $response = $this->call('post', '/pet/{petId}');
        $this->assertEquals(200, $response->status());
    }

    public function testdeletePet() {
        $response = $this->call('delete', '/pet/{petId}');
        $this->assertEquals(200, $response->status());
    }

    public function testuploadFile() {
        $response = $this->call('post', '/pet/{petId}/uploadImage');
        $this->assertEquals(200, $response->status());
    }

    public function testgetInventory() {
        $response = $this->call('get', '/store/inventory');
        $this->assertEquals(200, $response->status());
    }

    public function testplaceOrder() {
        $response = $this->call('post', '/store/order');
        $this->assertEquals(200, $response->status());
    }

    public function testgetOrderById() {
        $response = $this->call('get', '/store/order/{orderId}');
        $this->assertEquals(200, $response->status());
    }

    public function testdeleteOrder() {
        $response = $this->call('delete', '/store/order/{orderId}');
        $this->assertEquals(200, $response->status());
    }

    public function testcreateUser() {
        $response = $this->call('post', '/user');
        $this->assertEquals(200, $response->status());
    }

    public function testcreateUsersWithListInput() {
        $response = $this->call('post', '/user/createWithList');
        $this->assertEquals(200, $response->status());
    }

    public function testloginUser() {
        $response = $this->call('get', '/user/login');
        $this->assertEquals(200, $response->status());
    }

    public function testlogoutUser() {
        $response = $this->call('get', '/user/logout');
        $this->assertEquals(200, $response->status());
    }

    public function testgetUserByName() {
        $response = $this->call('get', '/user/{username}');
        $this->assertEquals(200, $response->status());
    }

    public function testupdateUser() {
        $response = $this->call('put', '/user/{username}');
        $this->assertEquals(200, $response->status());
    }

    public function testdeleteUser() {
        $response = $this->call('delete', '/user/{username}');
        $this->assertEquals(200, $response->status());
    }

