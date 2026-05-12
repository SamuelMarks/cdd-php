<?php

use Illuminate\Support\Facades\Route;

Route::put('/pet', 'updatePet');
Route::post('/pet', 'addPet');
Route::get('/pet/findByStatus', 'findPetsByStatus');
Route::get('/pet/findByTags', 'findPetsByTags');
Route::get('/pet/{petId}', 'getPetById');
Route::post('/pet/{petId}', 'updatePetWithForm');
Route::delete('/pet/{petId}', 'deletePet');
Route::post('/pet/{petId}/uploadImage', 'uploadFile');
Route::get('/store/inventory', 'getInventory');
Route::post('/store/order', 'placeOrder');
Route::get('/store/order/{orderId}', 'getOrderById');
Route::delete('/store/order/{orderId}', 'deleteOrder');
Route::post('/user', 'createUser');
Route::post('/user/createWithList', 'createUsersWithListInput');
Route::get('/user/login', 'loginUser');
Route::get('/user/logout', 'logoutUser');
Route::get('/user/{username}', 'getUserByName');
Route::put('/user/{username}', 'updateUser');
Route::delete('/user/{username}', 'deleteUser');
