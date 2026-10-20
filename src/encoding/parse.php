<?php

/**
 * Parses and validates OpenAPI Encoding and Media Type Objects.
 *
 * @package Cdd\Encoding
 */

declare(strict_types=1);

namespace Cdd\Encoding;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;

/**
 * Parses an Encoding Object from a PHP array definition, PHP array code snippet, or AST node.
 *
 * @param array<string, mixed>|string|Node $encoding The raw encoding array, emitted PHP array snippet, or AST node.
 * @return array<string, mixed> The parsed OpenAPI Encoding Object.
 */
function parse(array|string|Node $encoding): array
{
    if (is_string($encoding)) {
        $trimmed = trim($encoding);
        if ($trimmed === '') {
            return [];
        }
        $code = str_starts_with($trimmed, '<?php') ? $trimmed : "<?php return {$trimmed};";
        $parser = \Cdd\Classes\create_parser();
        try {
            $stmts = $parser->parse($code);
        } catch (\Throwable $e) {
            return [];
        }
        if ($stmts !== null) {
            foreach ($stmts as $stmt) {
                if ($stmt instanceof Return_ && $stmt->expr !== null) {
                    $evaluated = \Cdd\Classes\evaluate_ast_node($stmt->expr);
                    if (is_array($evaluated)) {
                        validateEncodingObject($evaluated);
                        return $evaluated;
                    }
                } elseif ($stmt instanceof Expression) {
                    $evaluated = \Cdd\Classes\evaluate_ast_node($stmt->expr);
                    if (is_array($evaluated)) {
                        validateEncodingObject($evaluated);
                        return $evaluated;
                    }
                }
            }
        }
        return [];
    }

    if ($encoding instanceof Node) {
        $evaluated = \Cdd\Classes\evaluate_ast_node($encoding);
        if (is_array($evaluated)) {
            validateEncodingObject($evaluated);
            return $evaluated;
        }
        return [];
    }

    validateEncodingObject($encoding);
    return $encoding;
}

/**
 * Validates a Media Type Object or Reference Object.
 *
 * @param mixed $mediaTypeObj The Media Type or Reference Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateMediaTypeOrReferenceObject(mixed $mediaTypeObj): void
{
    if (!is_array($mediaTypeObj)) {
        throw \Cdd\Exceptions\CddException::validation('Media Type must be an object');
    }
    if (isset($mediaTypeObj['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($mediaTypeObj);
        return;
    }
    if (isset($mediaTypeObj['schema']) && !is_array($mediaTypeObj['schema']) && !is_bool($mediaTypeObj['schema'])) {
        throw \Cdd\Exceptions\CddException::validation('Media Type "schema" must be an object');
    }
    if (isset($mediaTypeObj['itemSchema']) && !is_array($mediaTypeObj['itemSchema']) && !is_bool($mediaTypeObj['itemSchema'])) {
        throw \Cdd\Exceptions\CddException::validation('Media Type "itemSchema" must be an object');
    }
    if (isset($mediaTypeObj['examples']) && !is_array($mediaTypeObj['examples'])) {
        throw \Cdd\Exceptions\CddException::validation('Media Type "examples" must be a map');
    }
    if (isset($mediaTypeObj['example']) && isset($mediaTypeObj['examples'])) {
        throw \Cdd\Exceptions\CddException::validation('Media Type cannot contain both "example" and "examples"');
    }
    if (isset($mediaTypeObj['encoding']) && !is_array($mediaTypeObj['encoding'])) {
        throw \Cdd\Exceptions\CddException::validation('Media Type "encoding" must be a map');
    }
    if (isset($mediaTypeObj['prefixEncoding']) && !is_array($mediaTypeObj['prefixEncoding'])) {
        throw \Cdd\Exceptions\CddException::validation('Media Type "prefixEncoding" must be an array');
    }
    if (isset($mediaTypeObj['encoding']) && (isset($mediaTypeObj['prefixEncoding']) || isset($mediaTypeObj['itemEncoding']))) {
        throw \Cdd\Exceptions\CddException::validation('Media Type "encoding" cannot be present with "prefixEncoding" or "itemEncoding"');
    }
    if (isset($mediaTypeObj['encoding'])) {
        foreach ($mediaTypeObj['encoding'] as $enc) {
            \Cdd\Encoding\validateEncodingObject($enc);
        }
    }
    if (isset($mediaTypeObj['prefixEncoding'])) {
        foreach ($mediaTypeObj['prefixEncoding'] as $enc) {
            \Cdd\Encoding\validateEncodingObject($enc);
        }
    }
    if (isset($mediaTypeObj['itemEncoding'])) {
        \Cdd\Encoding\validateEncodingObject($mediaTypeObj['itemEncoding']);
    }
}

/**
 * Validates an Encoding Object.
 *
 * @param mixed $encodingObj The Encoding Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateEncodingObject(mixed $encodingObj): void
{
    if (!is_array($encodingObj)) {
        throw \Cdd\Exceptions\CddException::validation('Encoding must be an object');
    }
    if (isset($encodingObj['contentType']) && !is_string($encodingObj['contentType'])) {
        throw \Cdd\Exceptions\CddException::validation('Encoding "contentType" must be a string');
    }
    if (isset($encodingObj['headers'])) {
        if (!is_array($encodingObj['headers'])) {
            throw \Cdd\Exceptions\CddException::validation('Encoding "headers" must be a map');
        }
        foreach ($encodingObj['headers'] as $headerObj) {
            \Cdd\Responses\validateHeaderOrReferenceObject($headerObj);
        }
    }
    if (isset($encodingObj['style']) && !is_string($encodingObj['style'])) {
        throw \Cdd\Exceptions\CddException::validation('Encoding "style" must be a string');
    }
    if (isset($encodingObj['explode']) && !is_bool($encodingObj['explode'])) {
        throw \Cdd\Exceptions\CddException::validation('Encoding "explode" must be a boolean');
    }
    if (isset($encodingObj['allowReserved']) && !is_bool($encodingObj['allowReserved'])) {
        throw \Cdd\Exceptions\CddException::validation('Encoding "allowReserved" must be a boolean');
    }
    if (isset($encodingObj['encoding']) && !is_array($encodingObj['encoding'])) {
        throw \Cdd\Exceptions\CddException::validation('Encoding "encoding" must be a map');
    }
    if (isset($encodingObj['prefixEncoding']) && !is_array($encodingObj['prefixEncoding'])) {
        throw \Cdd\Exceptions\CddException::validation('Encoding "prefixEncoding" must be an array');
    }
    if (isset($encodingObj['encoding']) && (isset($encodingObj['prefixEncoding']) || isset($encodingObj['itemEncoding']))) {
        throw \Cdd\Exceptions\CddException::validation('Encoding "encoding" cannot be present with "prefixEncoding" or "itemEncoding"');
    }
    if (isset($encodingObj['encoding'])) {
        foreach ($encodingObj['encoding'] as $subEnc) {
            \Cdd\Encoding\validateEncodingObject($subEnc);
        }
    }
    if (isset($encodingObj['prefixEncoding'])) {
        foreach ($encodingObj['prefixEncoding'] as $subEnc) {
            \Cdd\Encoding\validateEncodingObject($subEnc);
        }
    }
    if (isset($encodingObj['itemEncoding'])) {
        \Cdd\Encoding\validateEncodingObject($encodingObj['itemEncoding']);
    }
}
