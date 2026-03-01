<?php

declare(strict_types=1);

declare (strict_types=1);
namespace Cdd\Encoding;

/**
 * Parses an Encoding Object from a PHP array definition.
 * 
 * @param array $encoding The PHP array representing the Encoding object.
 * @return array The validated and parsed Encoding object.
 */
function parse(array $encoding): array
{
    // In a full implementation, this could map specialized PHP annotations to Encoding.
    // For now, it simply returns the encoding array as a placeholder for further processing.
    return $encoding;
}
/**
 * Validates a Media Type Object or Reference Object.
 */
function validateMediaTypeOrReferenceObject(mixed $mediaTypeObj): void
{
    if (!is_array($mediaTypeObj)) {
        throw new \RuntimeException('Media Type must be an object');
    }
    if (isset($mediaTypeObj['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($mediaTypeObj);
        return;
    }
    if (isset($mediaTypeObj['schema']) && !is_array($mediaTypeObj['schema'])) {
        throw new \RuntimeException('Media Type "schema" must be an object');
    }
    if (isset($mediaTypeObj['itemSchema']) && !is_array($mediaTypeObj['itemSchema'])) {
        throw new \RuntimeException('Media Type "itemSchema" must be an object');
    }
    if (isset($mediaTypeObj['example']) && isset($mediaTypeObj['examples'])) {
        throw new \RuntimeException('Media Type cannot contain both "example" and "examples"');
    }
    if (isset($mediaTypeObj['examples']) && !is_array($mediaTypeObj['examples'])) {
        throw new \RuntimeException('Media Type "examples" must be a map');
    }
    if (isset($mediaTypeObj['encoding'])) {
        if (!is_array($mediaTypeObj['encoding'])) {
            throw new \RuntimeException('Media Type "encoding" must be a map');
        }
        foreach ($mediaTypeObj['encoding'] as $prop => $encodingObj) {
            \Cdd\Encoding\validateEncodingObject($encodingObj);
        }
    }
    if (isset($mediaTypeObj['prefixEncoding'])) {
        if (!is_array($mediaTypeObj['prefixEncoding'])) {
            throw new \RuntimeException('Media Type "prefixEncoding" must be an array');
        }
        foreach ($mediaTypeObj['prefixEncoding'] as $encodingObj) {
            \Cdd\Encoding\validateEncodingObject($encodingObj);
        }
    }
    if (isset($mediaTypeObj['itemEncoding'])) {
        \Cdd\Encoding\validateEncodingObject($mediaTypeObj['itemEncoding']);
    }
    $hasEncoding = isset($mediaTypeObj['encoding']);
    $hasPrefixEncoding = isset($mediaTypeObj['prefixEncoding']);
    $hasItemEncoding = isset($mediaTypeObj['itemEncoding']);
    if ($hasEncoding && ($hasPrefixEncoding || $hasItemEncoding)) {
        throw new \RuntimeException('Media Type "encoding" cannot be present with "prefixEncoding" or "itemEncoding"');
    }
}
/**
 * Validates an Encoding Object.
 */
function validateEncodingObject(mixed $encodingObj): void
{
    if (!is_array($encodingObj)) {
        throw new \RuntimeException('Encoding must be an object');
    }
    if (isset($encodingObj['contentType']) && !is_string($encodingObj['contentType'])) {
        throw new \RuntimeException('Encoding "contentType" must be a string');
    }
    if (isset($encodingObj['headers']) && !is_array($encodingObj['headers'])) {
        throw new \RuntimeException('Encoding "headers" must be a map');
    }
    if (isset($encodingObj['style']) && !is_string($encodingObj['style'])) {
        throw new \RuntimeException('Encoding "style" must be a string');
    }
    if (isset($encodingObj['explode']) && !is_bool($encodingObj['explode'])) {
        throw new \RuntimeException('Encoding "explode" must be a boolean');
    }
    if (isset($encodingObj['allowReserved']) && !is_bool($encodingObj['allowReserved'])) {
        throw new \RuntimeException('Encoding "allowReserved" must be a boolean');
    }
    $hasEncoding = isset($encodingObj['encoding']);
    $hasPrefixEncoding = isset($encodingObj['prefixEncoding']);
    $hasItemEncoding = isset($encodingObj['itemEncoding']);
    if ($hasEncoding && ($hasPrefixEncoding || $hasItemEncoding)) {
        throw new \RuntimeException('Encoding "encoding" cannot be present with "prefixEncoding" or "itemEncoding"');
    }
    if (isset($encodingObj['encoding'])) {
        if (!is_array($encodingObj['encoding'])) {
            throw new \RuntimeException('Encoding "encoding" must be a map');
        }
        foreach ($encodingObj['encoding'] as $subEnc) {
            \Cdd\Encoding\validateEncodingObject($subEnc);
        }
    }
    if (isset($encodingObj['prefixEncoding'])) {
        if (!is_array($encodingObj['prefixEncoding'])) {
            throw new \RuntimeException('Encoding "prefixEncoding" must be an array');
        }
        foreach ($encodingObj['prefixEncoding'] as $subEnc) {
            \Cdd\Encoding\validateEncodingObject($subEnc);
        }
    }
    if (isset($encodingObj['itemEncoding'])) {
        \Cdd\Encoding\validateEncodingObject($encodingObj['itemEncoding']);
    }
}
