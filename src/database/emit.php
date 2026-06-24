<?php

declare(strict_types=1);

namespace Cdd\Database;

/**
 * Emits Database connection and migration logic.
 *
 * @param array $schemas The OpenAPI schemas used to generate migrations
 * @return string
 */
function emit(array $schemas): string
{
    $out = "<?php\n\n/**\n * Auto-generated Database Connection\n */\n\n";
    $out .= "use Illuminate\\Database\\Capsule\\Manager as Capsule;\n";
    $out .= "use Illuminate\\Database\\Schema\\Blueprint;\n\n";

    $out .= "/**\n * Manages database connection and migrations.\n */\n";
    $out .= "class DatabaseConnection {\n";

    $out .= "    /**\n     * Initializes the database connection based on environment and CLI flags.\n     * @param bool \$ephemeral If true, forces an in-memory SQLite database.\n     * @return Capsule\n     */\n";
    $out .= "    public static function connect(bool \$ephemeral = false): Capsule {\n";
    $out .= "        \$capsule = new Capsule;\n";
    $out .= "        \$dbUrl = getenv('DATABASE_URL');\n";
    $out .= "        if (\$ephemeral || !\$dbUrl) {\n";
    $out .= "            \$capsule->addConnection([\n";
    $out .= "                'driver'   => 'sqlite',\n";
    $out .= "                'database' => ':memory:',\n";
    $out .= "                'prefix'   => '',\n";
    $out .= "            ]);\n";
    $out .= "        } else {\n";
    $out .= "            // Parse database url roughly\n";
    $out .= "            \$parsed = parse_url(\$dbUrl);\n";
    $out .= "            \$capsule->addConnection([\n";
    $out .= "                'driver'   => \$parsed['scheme'] === 'postgres' ? 'pgsql' : \$parsed['scheme'],\n";
    $out .= "                'host'     => \$parsed['host'] ?? '127.0.0.1',\n";
    $out .= "                'port'     => \$parsed['port'] ?? 5432,\n";
    $out .= "                'database' => ltrim(\$parsed['path'] ?? '', '/'),\n";
    $out .= "                'username' => \$parsed['user'] ?? 'root',\n";
    $out .= "                'password' => \$parsed['pass'] ?? '',\n";
    $out .= "                'charset'  => 'utf8',\n";
    $out .= "                'prefix'   => '',\n";
    $out .= "            ]);\n";
    $out .= "        }\n";
    $out .= "        \$capsule->setAsGlobal();\n";
    $out .= "        \$capsule->bootEloquent();\n";
    $out .= "        return \$capsule;\n";
    $out .= "    }\n\n";

    $out .= "    /**\n     * Runs schema migrations for all defined entities.\n     * @return void\n     */\n";
    $out .= "    public static function migrate(): void {\n";
    foreach ($schemas as $name => $schema) {
        $tableName = strtolower($name) . 's'; // simple pluralization
        $out .= "        if (!Capsule::schema()->hasTable('{$tableName}')) {\n";
        $out .= "            Capsule::schema()->create('{$tableName}', function (Blueprint \$table) {\n";
        $out .= "                \$table->id();\n";
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propDef) {
                if ($propName === 'id') {
                    continue;
                }
                $type = $propDef['type'] ?? 'string';
                $dbType = 'string';
                if ($type === 'integer') {
                    $dbType = 'integer';
                }
                if ($type === 'boolean') {
                    $dbType = 'boolean';
                }
                if ($type === 'number') {
                    $dbType = 'float';
                }
                $out .= "                \$table->{$dbType}('{$propName}')->nullable();\n";
            }
        }
        $out .= "                \$table->timestamps();\n";
        $out .= "            });\n";
        $out .= "        }\n";
    }
    $out .= "    }\n";

    $out .= "}\n";
    return $out;
}
