<?php

/**
 * Typed domain exception hierarchy for `cdd-php`.
 *
 * @package Cdd\Exceptions
 */

declare(strict_types=1);

namespace Cdd\Exceptions;

/**
 * Base exception for all `cdd-php` compiler, validation, runtime, and transport errors.
 */
class CddException extends \RuntimeException
{
    /**
     * Creates a validation exception for an OpenAPI or Arazzo specification error.
     *
     * @param string $message Detailed validation error message.
     * @return self
     */
    public static function validation(string $message): self
    {
        return new self($message);
    }

    /**
     * Creates a parsing exception when reading JSON or PHP source code fails.
     *
     * @param string $message Detailed parse error message.
     * @return self
     */
    public static function parseError(string $message): self
    {
        return new self($message);
    }

    /**
     * Creates an emission exception when encoding or code generation fails.
     *
     * @param string $message Detailed emission error message.
     * @return self
     */
    public static function emitError(string $message): self
    {
        return new self($message);
    }
}
