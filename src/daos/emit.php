<?php

declare(strict_types=1);

namespace Cdd\Daos;

/**
 * emit_modular
 */
function emit_modular(array $schemas): array
{
    $files = [];

    // Interfaces
    foreach ($schemas as $name => $schema) {
        $files["{$name}DaoInterface.php"] = "<?php\n\nnamespace Api\\Daos;\n\n/**\n * Data Access Object interface for {$name}\n */\ninterface {$name}DaoInterface {\n    public function getAll(): array;\n    public function getById(\$id): ?object;\n    public function create(array \$data): object;\n    public function update(\$id, array \$data): ?object;\n    public function delete(\$id): bool;\n}\n";
    }

    // Stubs
    foreach ($schemas as $name => $schema) {
        $files["Stub{$name}Dao.php"] = "<?php\n\nnamespace Api\\Daos;\n\n/**\n * Stub DAO implementation for {$name}\n */\nclass Stub{$name}Dao implements {$name}DaoInterface {\n    public function getAll(): array { throw new \\Api\\Exceptions\\MockServerError('Not implemented'); }\n    public function getById(\$id): ?object { throw new \\Api\\Exceptions\\MockServerError('Not implemented'); }\n    public function create(array \$data): object { throw new \\Api\\Exceptions\\MockServerError('Not implemented'); }\n    public function update(\$id, array \$data): ?object { throw new \\Api\\Exceptions\\MockServerError('Not implemented'); }\n    public function delete(\$id): bool { throw new \\Api\\Exceptions\\MockServerError('Not implemented'); }\n}\n";
    }

    // Concrete
    foreach ($schemas as $name => $schema) {
        $files["Concrete{$name}Dao.php"] = "<?php\n\nnamespace Api\\Daos;\n\nuse Api\\Models\\{$name};\n\n/**\n * Concrete DB-backed DAO implementation for {$name}\n */\nclass Concrete{$name}Dao implements {$name}DaoInterface {\n    public function getAll(): array { return {$name}::all()->all(); }\n    public function getById(\$id): ?object { return {$name}::find(\$id); }\n    public function create(array \$data): object { return {$name}::create(\$data); }\n    public function update(\$id, array \$data): ?object { \$m = {$name}::find(\$id); if(\$m){ \$m->update(\$data); return \$m; } return null; }\n    public function delete(\$id): bool { \$m = {$name}::find(\$id); if(\$m){ return \$m->delete(); } return false; }\n}\n";
    }

    // Factory
    $files["DaoFactory.php"] = "<?php\n\nnamespace Api\\Daos;\n\nclass DaoFactory {\n    public static function create(string \$schema, bool \$ephemeral = false): object {\n        \$dbUrl = getenv('DATABASE_URL');\n        if (!\$dbUrl && !\$ephemeral) {\n            \$class = '\\\\Api\\\\Daos\\\\Stub' . \$schema . 'Dao';\n            return new \$class();\n        }\n        \$class = '\\\\Api\\\\Daos\\\\Concrete' . \$schema . 'Dao';\n        return new \$class();\n    }\n}\n";

    return $files;
}

/**
 * emit
 */
function emit(array $schemas): string
{
    $out = "<?php\n\n/**\n * Auto-generated DAOs\n */\n\n";

    // Dao Interfaces
    foreach ($schemas as $name => $schema) {
        $out .= "/**\n * Data Access Object interface for {$name}\n */\n";
        $out .= "interface {$name}DaoInterface {\n";
        $out .= "    /**\n     * Retrieves all {$name} records\n     * @return array<{$name}>\n     */\n";
        $out .= "    public function getAll(): array;\n";
        $out .= "    /**\n     * Retrieves a {$name} by ID\n     * @param int|string \$id The identifier\n     * @return {$name}|null\n     */\n";
        $out .= "    public function getById(\$id): ?object;\n";
        $out .= "    /**\n     * Creates a new {$name}\n     * @param array \$data The data\n     * @return {$name}\n     */\n";
        $out .= "    public function create(array \$data): object;\n";
        $out .= "    /**\n     * Updates a {$name}\n     * @param int|string \$id The identifier\n     * @param array \$data The data\n     * @return {$name}|null\n     */\n";
        $out .= "    public function update(\$id, array \$data): ?object;\n";
        $out .= "    /**\n     * Deletes a {$name}\n     * @param int|string \$id The identifier\n     * @return bool\n     */\n";
        $out .= "    public function delete(\$id): bool;\n";
        $out .= "}\n\n";
    }

    // Stub DAOs
    foreach ($schemas as $name => $schema) {
        $out .= "/**\n * Stub DAO implementation for {$name}\n * Returns empty responses or throws NotImplemented\n */\n";
        $out .= "class Stub{$name}Dao implements {$name}DaoInterface {\n";
        $out .= "    /**\n     * Retrieves all {$name} records (stub)\n     * @return array\n     */\n";
        $out .= "    public function getAll(): array { throw new \\RuntimeException('Not implemented'); }\n";
        $out .= "    /**\n     * Retrieves a {$name} by ID (stub)\n     * @param int|string \$id\n     * @return {$name}|null\n     */\n";
        $out .= "    public function getById(\$id): ?object { throw new \\RuntimeException('Not implemented'); }\n";
        $out .= "    /**\n     * Creates a new {$name} (stub)\n     * @param array \$data\n     * @return {$name}\n     */\n";
        $out .= "    public function create(array \$data): object { throw new \\RuntimeException('Not implemented'); }\n";
        $out .= "    /**\n     * Updates a {$name} (stub)\n     * @param int|string \$id\n     * @param array \$data\n     * @return {$name}|null\n     */\n";
        $out .= "    public function update(\$id, array \$data): ?object { throw new \\RuntimeException('Not implemented'); }\n";
        $out .= "    /**\n     * Deletes a {$name} (stub)\n     * @param int|string \$id\n     * @return bool\n     */\n";
        $out .= "    public function delete(\$id): bool { throw new \\RuntimeException('Not implemented'); }\n";
        $out .= "}\n\n";
    }

    // Concrete DAOs
    foreach ($schemas as $name => $schema) {
        $out .= "/**\n * Concrete DB-backed DAO implementation for {$name}\n */\n";
        $out .= "class Concrete{$name}Dao implements {$name}DaoInterface {\n";
        $out .= "    /**\n     * Retrieves all {$name} records from DB\n     * @return array\n     */\n";
        $out .= "    public function getAll(): array { return {$name}::all()->all(); }\n";
        $out .= "    /**\n     * Retrieves a {$name} by ID from DB\n     * @param int|string \$id\n     * @return {$name}|null\n     */\n";
        $out .= "    public function getById(\$id): ?object { return {$name}::find(\$id); }\n";
        $out .= "    /**\n     * Creates a new {$name} in DB\n     * @param array \$data\n     * @return {$name}\n     */\n";
        $out .= "    public function create(array \$data): object { return {$name}::create(\$data); }\n";
        $out .= "    /**\n     * Updates a {$name} in DB\n     * @param int|string \$id\n     * @param array \$data\n     * @return {$name}|null\n     */\n";
        $out .= "    public function update(\$id, array \$data): ?object { \$m = {$name}::find(\$id); if(\$m){ \$m->update(\$data); return \$m; } return null; }\n";
        $out .= "    /**\n     * Deletes a {$name} in DB\n     * @param int|string \$id\n     * @return bool\n     */\n";
        $out .= "    public function delete(\$id): bool { \$m = {$name}::find(\$id); if(\$m){ return \$m->delete(); } return false; }\n";
        $out .= "}\n\n";
    }

    // Factory
    $out .= "/**\n * Dependency Injection Factory for DAOs\n */\n";
    $out .= "class DaoFactory {\n";
    $out .= "    /**\n     * Creates the appropriate DAO based on environment and CLI arguments\n     * @param string \$schema The schema name\n     * @param bool \$ephemeral Whether to use ephemeral DB\n     * @return object The DAO implementation\n     */\n";
    $out .= "    public static function create(string \$schema, bool \$ephemeral = false): object {\n";
    $out .= "        \$dbUrl = getenv('DATABASE_URL');\n";
    $out .= "        if (!\$dbUrl && !\$ephemeral) {\n";
    $out .= "            \$class = 'Stub' . \$schema . 'Dao';\n";
    $out .= "            return new \$class();\n";
    $out .= "        }\n";
    $out .= "        \$class = 'Concrete' . \$schema . 'Dao';\n";
    $out .= "        return new \$class();\n";
    $out .= "    }\n";
    $out .= "}\n";

    return $out;
}
