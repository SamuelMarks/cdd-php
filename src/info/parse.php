<?php

/**
 * Parses and validates OpenAPI Info, Contact, License, Tag, and ExternalDocs Objects.
 *
 * @package Cdd\Info
 */

declare(strict_types=1);

namespace Cdd\Info;

/**
 * Parses Info Object data (from a PHPDoc docblock string, associative Info array, or scalar parameters) into an OpenAPI Info Object.
 *
 * @param array<string, mixed>|string $titleOrDocblock The API title, emitted PHPDoc docblock string, or associative Info Object array.
 * @param string $version The API version (when passing scalar arguments).
 * @param string|null $description Optional description (when passing scalar arguments).
 * @return array<string, mixed> The OpenAPI Info Object.
 */
function parse(array|string $titleOrDocblock = 'API', string $version = '1.0.0', ?string $description = null): array
{
    if (is_array($titleOrDocblock)) {
        validateInfoObject($titleOrDocblock);
        return $titleOrDocblock;
    }

    $trimmed = trim($titleOrDocblock);
    if (str_starts_with($trimmed, '/**')) {
        $parsedDoc = \Cdd\Docstrings\parse($trimmed);
        $lines = $parsedDoc['description'] !== '' ? explode("
", $parsedDoc['description']) : [];
        $headerLine = array_shift($lines) ?? 'API (v1.0.0)';
        $parsedTitle = $headerLine;
        $parsedVer = $version;
        if (preg_match('/^(.*?)\s*\(v([^)]+)\)$/', trim($headerLine), $m)) {
            $parsedTitle = trim($m[1]);
            $parsedVer = trim($m[2]);
        }
        $info = [
            'title' => $parsedTitle !== '' ? $parsedTitle : 'API',
            'version' => $parsedVer !== '' ? $parsedVer : '1.0.0',
        ];
        if (isset($parsedDoc['tags']['summary'][0])) {
            $info['summary'] = trim((string) $parsedDoc['tags']['summary'][0]);
        }
        $descBody = trim(implode("
", $lines));
        if ($descBody !== '') {
            $info['description'] = $descBody;
        }
        if (isset($parsedDoc['tags']['termsOfService'][0])) {
            $info['termsOfService'] = trim((string) $parsedDoc['tags']['termsOfService'][0]);
        }
        $contact = [];
        if (isset($parsedDoc['tags']['contact-name'][0])) {
            $contact['name'] = trim((string) $parsedDoc['tags']['contact-name'][0]);
        }
        if (isset($parsedDoc['tags']['contact-url'][0])) {
            $contact['url'] = trim((string) $parsedDoc['tags']['contact-url'][0]);
        }
        if (isset($parsedDoc['tags']['contact-email'][0])) {
            $contact['email'] = trim((string) $parsedDoc['tags']['contact-email'][0]);
        }
        if (!empty($contact)) {
            $info['contact'] = $contact;
        }
        $license = [];
        if (isset($parsedDoc['tags']['license-name'][0])) {
            $license['name'] = trim((string) $parsedDoc['tags']['license-name'][0]);
        }
        if (isset($parsedDoc['tags']['license-identifier'][0])) {
            $license['identifier'] = trim((string) $parsedDoc['tags']['license-identifier'][0]);
        }
        if (isset($parsedDoc['tags']['license-url'][0])) {
            $license['url'] = trim((string) $parsedDoc['tags']['license-url'][0]);
        }
        if (!empty($license)) {
            $info['license'] = $license;
        }
        return $info;
    }

    $info = [
        'title' => $titleOrDocblock,
        'version' => $version,
    ];
    if ($description !== null) {
        $info['description'] = $description;
    }
    return $info;
}

/**
 * Validates an OpenAPI Info Object.
 *
 * @param mixed $info The Info Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateInfoObject(mixed $info): void
{
    if (!is_array($info)) {
        throw new \RuntimeException('Field "info" must be an Info Object');
    }
    if (!isset($info['title']) || !is_string($info['title'])) {
        throw new \RuntimeException('Info object must contain a "title" string');
    }
    if (!isset($info['version']) || !is_string($info['version'])) {
        throw new \RuntimeException('Info object must contain a "version" string');
    }
    if (isset($info['summary']) && !is_string($info['summary'])) {
        throw new \RuntimeException('Info "summary" must be a string');
    }
    if (isset($info['description']) && !is_string($info['description'])) {
        throw new \RuntimeException('Info "description" must be a string');
    }
    if (isset($info['termsOfService']) && !is_string($info['termsOfService'])) {
        throw new \RuntimeException('Info "termsOfService" must be a string');
    }
    if (isset($info['contact'])) {
        validateContactObject($info['contact']);
    }
    if (isset($info['license'])) {
        validateLicenseObject($info['license']);
    }
}

/**
 * Validates a Contact Object.
 *
 * @param mixed $contact The Contact Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateContactObject(mixed $contact): void
{
    if (!is_array($contact)) {
        throw new \RuntimeException('Contact must be an object');
    }
    if (isset($contact['name']) && !is_string($contact['name'])) {
        throw new \RuntimeException('Contact "name" must be a string');
    }
    if (isset($contact['url']) && !is_string($contact['url'])) {
        throw new \RuntimeException('Contact "url" must be a string');
    }
    if (isset($contact['email']) && !is_string($contact['email'])) {
        throw new \RuntimeException('Contact "email" must be a string');
    }
}

/**
 * Validates a License Object.
 *
 * @param mixed $license The License Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateLicenseObject(mixed $license): void
{
    if (!is_array($license)) {
        throw new \RuntimeException('License must be an object');
    }
    if (!isset($license['name']) || !is_string($license['name'])) {
        throw new \RuntimeException('License object must contain a "name" string');
    }
    if (isset($license['identifier']) && !is_string($license['identifier'])) {
        throw new \RuntimeException('License "identifier" must be a string');
    }
    if (isset($license['url']) && !is_string($license['url'])) {
        throw new \RuntimeException('License "url" must be a string');
    }
    if (isset($license['identifier']) && isset($license['url'])) {
        throw new \RuntimeException('License "identifier" and "url" are mutually exclusive');
    }
}

/**
 * Validates a Tag Object.
 *
 * @param mixed $tag The Tag Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateTagObject(mixed $tag): void
{
    if (!is_array($tag)) {
        throw new \RuntimeException('Tag must be an object');
    }
    if (!isset($tag['name']) || !is_string($tag['name'])) {
        throw new \RuntimeException('Tag object must contain a "name" string');
    }
    if (isset($tag['summary']) && !is_string($tag['summary'])) {
        throw new \RuntimeException('Tag "summary" must be a string');
    }
    if (isset($tag['description']) && !is_string($tag['description'])) {
        throw new \RuntimeException('Tag "description" must be a string');
    }
    if (isset($tag['parent']) && !is_string($tag['parent'])) {
        throw new \RuntimeException('Tag "parent" must be a string');
    }
    if (isset($tag['kind']) && !is_string($tag['kind'])) {
        throw new \RuntimeException('Tag "kind" must be a string');
    }
    if (isset($tag['externalDocs'])) {
        validateExternalDocsObject($tag['externalDocs']);
    }
}

/**
 * Validates an External Documentation Object.
 *
 * @param mixed $docs The External Documentation Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateExternalDocsObject(mixed $docs): void
{
    if (!is_array($docs)) {
        throw new \RuntimeException('Field "externalDocs" must be an External Documentation Object');
    }
    if (!isset($docs['url']) || !is_string($docs['url'])) {
        throw new \RuntimeException('External Documentation Object must contain a "url" string');
    }
    if (isset($docs['description']) && !is_string($docs['description'])) {
        throw new \RuntimeException('External Documentation "description" must be a string');
    }
}
