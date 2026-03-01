<?php

declare(strict_types=1);

declare (strict_types=1);
namespace Cdd\Info;

/**
 * Parses Info Object data into an OpenAPI Info Object.
 */
function parse(string $title, string $version, string $description = ''): array
{
    $info = ['title' => $title, 'version' => $version];
    if ($description !== '') {
        $info['description'] = $description;
    }
    return $info;
}
/**
 * Validates an Info Object.
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
        \Cdd\Info\validateContactObject($info['contact']);
    }
    if (isset($info['license'])) {
        \Cdd\Info\validateLicenseObject($info['license']);
    }
}
/**
 * Validates a Contact Object.
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
        \Cdd\Info\validateExternalDocsObject($tag['externalDocs']);
    }
}
/**
 * Validates an External Documentation Object.
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
