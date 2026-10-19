<?php

/**
 * Emits Data Access Object (DAO) interfaces, stubs, concrete implementations, and factories.
 *
 * @package Cdd\Daos
 */

declare(strict_types=1);

namespace Cdd\Daos;

/**
 * Emits modular DAO files for each OpenAPI schema.
 *
 * @param array<string, array<string, mixed>> $schemas Map of schema names to OpenAPI Schema Objects.
 * @return array<string, string> Map of relative filenames to generated PHP source code.
 */
function emit_modular(array $schemas): array
{
    $files = [];
    $files['MockServerError.php'] = "<?php

declare(strict_types=1);

namespace Api\Exceptions;

/**
 * Exception thrown when a stub DAO method is called without a backing database.
 */
class MockServerError extends \RuntimeException {}
";

    // Interfaces
    foreach ($schemas as $name => $schema) {
        $files["{$name}DaoInterface.php"] = "<?php

declare(strict_types=1);

namespace Api\Daos;

/**
 * Data Access Object interface for {$name}
 */
interface {$name}DaoInterface {
    public function getAll(): array;
    public function getById(int|string \$id): ?object;
    public function create(array \$data): object;
    public function update(int|string \$id, array \$data): ?object;
    public function delete(int|string \$id): bool;
}
";
    }

    // Stubs
    foreach ($schemas as $name => $schema) {
        $files["Stub{$name}Dao.php"] = "<?php

declare(strict_types=1);

namespace Api\Daos;

/**
 * Stub DAO implementation for {$name}
 */
class Stub{$name}Dao implements {$name}DaoInterface {
    public function getAll(): array { throw new \Api\Exceptions\MockServerError('Not implemented'); }
    public function getById(int|string \$id): ?object { throw new \Api\Exceptions\MockServerError('Not implemented'); }
    public function create(array \$data): object { throw new \Api\Exceptions\MockServerError('Not implemented'); }
    public function update(int|string \$id, array \$data): ?object { throw new \Api\Exceptions\MockServerError('Not implemented'); }
    public function delete(int|string \$id): bool { throw new \Api\Exceptions\MockServerError('Not implemented'); }
}
";
    }

    // Concrete
    foreach ($schemas as $name => $schema) {
        $files["Concrete{$name}Dao.php"] = "<?php

declare(strict_types=1);

namespace Api\Daos;

use Api\Models\{$name};

/**
 * Concrete DB-backed DAO implementation for {$name}
 */
class Concrete{$name}Dao implements {$name}DaoInterface {
    public function getAll(): array { return {$name}::all()->all(); }
    public function getById(int|string \$id): ?object { return {$name}::find(\$id); }
    public function create(array \$data): object { return {$name}::create(\$data); }
    public function update(int|string \$id, array \$data): ?object { \$m = {$name}::find(\$id); if(\$m){ \$m->update(\$data); return \$m; } return null; }
    public function delete(int|string \$id): bool { \$m = {$name}::find(\$id); if(\$m){ return (bool) \$m->delete(); } return false; }
}
";
    }

    // Factory
    $files['DaoFactory.php'] = "<?php

declare(strict_types=1);

namespace Api\Daos;

class DaoFactory {
    public static function create(string \$schema, bool \$ephemeral = false): object {
        \$dbUrl = getenv('DATABASE_URL');
        if (!\$dbUrl && !\$ephemeral) {
            \$class = '\Api\Daos\Stub' . \$schema . 'Dao';
            return new \$class();
        }
        \$class = '\Api\Daos\Concrete' . \$schema . 'Dao';
        return new \$class();
    }
}
";

    return $files;
}

/**
 * Emits a single combined PHP file containing DAO interfaces, stubs, concrete classes, and factory.
 *
 * @param array<string, array<string, mixed>> $schemas Map of schema names to OpenAPI Schema Objects.
 * @return string The generated PHP source code.
 */
function emit(array $schemas): string
{
    $out = "<?php

/**
 * Auto-generated DAOs
 */

declare(strict_types=1);

";

    // Dao Interfaces
    foreach ($schemas as $name => $schema) {
        $out .= "/**
 * Data Access Object interface for {$name}
 */
";
        $out .= "interface {$name}DaoInterface {
";
        $out .= "    /**
     * Retrieves all {$name} records
     * @return array<{$name}>
     */
";
        $out .= "    public function getAll(): array;
";
        $out .= "    /**
     * Retrieves a {$name} by ID
     * @param int|string \$id The identifier
     * @return {$name}|null
     */
";
        $out .= "    public function getById(int|string \$id): ?object;
";
        $out .= "    /**
     * Creates a new {$name}
     * @param array \$data The data
     * @return {$name}
     */
";
        $out .= "    public function create(array \$data): object;
";
        $out .= "    /**
     * Updates a {$name}
     * @param int|string \$id The identifier
     * @param array \$data The data
     * @return {$name}|null
     */
";
        $out .= "    public function update(int|string \$id, array \$data): ?object;
";
        $out .= "    /**
     * Deletes a {$name}
     * @param int|string \$id The identifier
     * @return bool
     */
";
        $out .= "    public function delete(int|string \$id): bool;
";
        $out .= "}

";
    }

    // Stub DAOs
    foreach ($schemas as $name => $schema) {
        $out .= "/**
 * Stub DAO implementation for {$name}
 * Returns empty responses or throws NotImplemented
 */
";
        $out .= "class Stub{$name}Dao implements {$name}DaoInterface {
";
        $out .= "    /**
     * Retrieves all {$name} records (stub)
     * @return array
     */
";
        $out .= "    public function getAll(): array { throw new \RuntimeException('Not implemented'); }
";
        $out .= "    /**
     * Retrieves a {$name} by ID (stub)
     * @param int|string \$id
     * @return {$name}|null
     */
";
        $out .= "    public function getById(int|string \$id): ?object { throw new \RuntimeException('Not implemented'); }
";
        $out .= "    /**
     * Creates a new {$name} (stub)
     * @param array \$data
     * @return {$name}
     */
";
        $out .= "    public function create(array \$data): object { throw new \RuntimeException('Not implemented'); }
";
        $out .= "    /**
     * Updates a {$name} (stub)
     * @param int|string \$id
     * @param array \$data
     * @return {$name}|null
     */
";
        $out .= "    public function update(int|string \$id, array \$data): ?object { throw new \RuntimeException('Not implemented'); }
";
        $out .= "    /**
     * Deletes a {$name} (stub)
     * @param int|string \$id
     * @return bool
     */
";
        $out .= "    public function delete(int|string \$id): bool { throw new \RuntimeException('Not implemented'); }
";
        $out .= "}

";
    }

    // Concrete DAOs
    foreach ($schemas as $name => $schema) {
        $out .= "/**
 * Concrete DB-backed DAO implementation for {$name}
 */
";
        $out .= "class Concrete{$name}Dao implements {$name}DaoInterface {
";
        $out .= "    /**
     * Retrieves all {$name} records from DB
     * @return array
     */
";
        $out .= "    public function getAll(): array { return {$name}::all()->all(); }
";
        $out .= "    /**
     * Retrieves a {$name} by ID from DB
     * @param int|string \$id
     * @return {$name}|null
     */
";
        $out .= "    public function getById(int|string \$id): ?object { return {$name}::find(\$id); }
";
        $out .= "    /**
     * Creates a new {$name} in DB
     * @param array \$data
     * @return {$name}
     */
";
        $out .= "    public function create(array \$data): object { return {$name}::create(\$data); }
";
        $out .= "    /**
     * Updates a {$name} in DB
     * @param int|string \$id
     * @param array \$data
     * @return {$name}|null
     */
";
        $out .= "    public function update(int|string \$id, array \$data): ?object { \$m = {$name}::find(\$id); if(\$m){ \$m->update(\$data); return \$m; } return null; }
";
        $out .= "    /**
     * Deletes a {$name} in DB
     * @param int|string \$id
     * @return bool
     */
";
        $out .= "    public function delete(int|string \$id): bool { \$m = {$name}::find(\$id); if(\$m){ return (bool) \$m->delete(); } return false; }
";
        $out .= "}

";
    }

    // Factory
    $out .= "/**
 * Dependency Injection Factory for DAOs
 */
";
    $out .= "class DaoFactory {
";
    $out .= "    /**
     * Creates the appropriate DAO based on environment and CLI arguments
     * @param string \$schema The schema name
     * @param bool \$ephemeral Whether to use ephemeral DB
     * @return object The DAO implementation
     */
";
    $out .= "    public static function create(string \$schema, bool \$ephemeral = false): object {
";
    $out .= "        \$dbUrl = getenv('DATABASE_URL');
";
    $out .= "        if (!\$dbUrl && !\$ephemeral) {
";
    $out .= "            \$class = 'Stub' . \$schema . 'Dao';
";
    $out .= "            return new \$class();
";
    $out .= "        }
";
    $out .= "        \$class = 'Concrete' . \$schema . 'Dao';
";
    $out .= "        return new \$class();
";
    $out .= "    }
";
    $out .= "}
";

    return $out;
}
