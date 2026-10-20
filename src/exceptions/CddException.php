<?php

/**
 * Typed domain exception hierarchy and error code enum for `cdd-php`.
 *
 * @package Cdd\Exceptions
 */

declare(strict_types=1);

namespace Cdd\Exceptions;

/**
 * Exhaustive domain error codes for `cdd-php` compiler, validation, runtime, and transport operations.
 */
enum CddErrorCode: string
{
    case JsonDecodeError = 'JSON_DECODE_ERROR';
    case JsonEncodeError = 'JSON_ENCODE_ERROR';
    case OpenApiValidationError = 'OPENAPI_VALIDATION_ERROR';
    case Swagger2ConversionError = 'SWAGGER2_CONVERSION_ERROR';
    case ArazzoValidationError = 'ARAZZO_VALIDATION_ERROR';
    case ArazzoRuntimeError = 'ARAZZO_RUNTIME_ERROR';
    case ReferenceResolutionError = 'REFERENCE_RESOLUTION_ERROR';
    case AstParseError = 'AST_PARSE_ERROR';
    case CodeEmissionError = 'CODE_EMISSION_ERROR';
    case CliArgumentError = 'CLI_ARGUMENT_ERROR';
    case FileSystemError = 'FILE_SYSTEM_ERROR';
    case McpProtocolError = 'MCP_PROTOCOL_ERROR';
    case JsonRpcTransportError = 'JSON_RPC_TRANSPORT_ERROR';
}

/**
 * Base exception for all `cdd-php` compiler, validation, runtime, and transport errors.
 */
class CddException extends \RuntimeException
{
    /**
     * @var CddErrorCode The specific domain error code for this exception.
     */
    public readonly CddErrorCode $errorCode;

    /**
     * @var string|null Optional JSON Pointer or specification path context where the error occurred.
     */
    public readonly ?string $contextPath;

    /**
     * @var string|null Optional file path associated with the error.
     */
    public readonly ?string $sourceFile;

    /**
     * Constructs a new `CddException` instance.
     *
     * @param string $message Detailed human-readable error message.
     * @param CddErrorCode $errorCode Typed domain error code.
     * @param string|null $contextPath Optional JSON pointer or spec path context.
     * @param string|null $sourceFile Optional file path associated with the error.
     * @param \Throwable|null $previous Optional underlying exception.
     */
    public function __construct(
        string $message,
        CddErrorCode $errorCode = CddErrorCode::OpenApiValidationError,
        ?string $contextPath = null,
        ?string $sourceFile = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->contextPath = $contextPath;
        $this->sourceFile = $sourceFile;
    }

    /**
     * Creates a validation exception for an OpenAPI specification error.
     *
     * @param string $message Detailed validation error message.
     * @param string|null $contextPath Optional specification path.
     * @return self
     */
    public static function validation(string $message, ?string $contextPath = null): self
    {
        return new self($message, CddErrorCode::OpenApiValidationError, $contextPath);
    }

    /**
     * Creates a validation exception for an Arazzo specification error.
     *
     * @param string $message Detailed Arazzo validation error message.
     * @param string|null $contextPath Optional specification path.
     * @return self
     */
    public static function arazzoValidation(string $message, ?string $contextPath = null): self
    {
        return new self($message, CddErrorCode::ArazzoValidationError, $contextPath);
    }

    /**
     * Creates a runtime exception for Arazzo workflow execution or reusable component resolution.
     *
     * @param string $message Detailed runtime error message.
     * @param string|null $contextPath Optional workflow/step path.
     * @return self
     */
    public static function arazzoRuntime(string $message, ?string $contextPath = null): self
    {
        return new self($message, CddErrorCode::ArazzoRuntimeError, $contextPath);
    }

    /**
     * Creates a JSON decoding exception.
     *
     * @param string $message Detailed JSON decoding error message.
     * @param string|null $sourceFile Optional source file path.
     * @return self
     */
    public static function jsonDecode(string $message, ?string $sourceFile = null): self
    {
        return new self($message, CddErrorCode::JsonDecodeError, null, $sourceFile);
    }

    /**
     * Creates a JSON encoding exception.
     *
     * @param string $message Detailed JSON encoding error message.
     * @return self
     */
    public static function jsonEncode(string $message): self
    {
        return new self($message, CddErrorCode::JsonEncodeError);
    }

    /**
     * Creates a parsing exception when reading JSON or PHP source code fails.
     *
     * @param string $message Detailed parse error message.
     * @param string|null $sourceFile Optional source file path.
     * @return self
     */
    public static function parseError(string $message, ?string $sourceFile = null): self
    {
        return new self($message, CddErrorCode::AstParseError, null, $sourceFile);
    }

    /**
     * Creates an emission exception when encoding or code generation fails.
     *
     * @param string $message Detailed emission error message.
     * @return self
     */
    public static function emitError(string $message): self
    {
        return new self($message, CddErrorCode::CodeEmissionError);
    }

    /**
     * Creates an MCP protocol or sampling exception.
     *
     * @param string $message Detailed MCP error message.
     * @return self
     */
    public static function mcpError(string $message): self
    {
        return new self($message, CddErrorCode::McpProtocolError);
    }
}
