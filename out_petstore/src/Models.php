<?php

class Order extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'id',
        'petId',
        'quantity',
        'shipDate',
        'status',
        'complete',
    ];
    protected $casts = [
        'id' => 'integer',
        'petId' => 'integer',
        'quantity' => 'integer',
        'complete' => 'boolean',
    ];
}

class Category extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'id',
        'name',
    ];
    protected $casts = [
        'id' => 'integer',
    ];
}

class User extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'id',
        'username',
        'firstName',
        'lastName',
        'email',
        'password',
        'phone',
        'userStatus',
    ];
    protected $casts = [
        'id' => 'integer',
        'userStatus' => 'integer',
    ];
}

class Tag extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'id',
        'name',
    ];
    protected $casts = [
        'id' => 'integer',
    ];
}

class Pet extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'id',
        'name',
        'category',
        'photoUrls',
        'tags',
        'status',
    ];
    protected $casts = [
        'id' => 'integer',
        'photoUrls' => 'array',
        'tags' => 'array',
    ];
}

class ApiResponse extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'code',
        'type',
        'message',
    ];
    protected $casts = [
        'code' => 'integer',
    ];
}

/**
 * @requestBody
 * List of user object
 */
class UserArray extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
    ];
    protected $casts = [
    ];
}

/**
 * @securityScheme
 * @type oauth2
 * @flow implicit {"authorizationUrl":"https:\/\/petstore3.swagger.io\/oauth\/authorize","scopes":{"write:pets":"modify pets in your account","read:pets":"read your pets"}}
 */
class petstore_auth
{
}

/**
 * @securityScheme
 * @type apiKey
 * @in header
 * @name api_key
 */
class api_key
{
}
