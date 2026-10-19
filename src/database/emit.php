<?php

/**
 * Emits database connection and schema migration logic for generated servers.
 *
 * @package Cdd\Database
 */

declare(strict_types=1);

namespace Cdd\Database;

/**
 * Emits Database connection and migration logic.
 *
 * @param array<string, array<string, mixed>> $schemas The OpenAPI schemas used to generate migrations.
 * @param string $namespace Optional PHP namespace for the generated DatabaseConnection class.
 * @return string The generated DatabaseConnection PHP code.
 */
function emit(array $schemas, string $namespace = ''): string
{
    $out = "<?php

/**
 * Auto-generated Database Connection
 */

declare(strict_types=1);

";
    if ($namespace !== '') {
        $out .= "namespace {$namespace};

";
    }
    $out .= "use Illuminate\Database\Capsule\Manager as Capsule;
";
    $out .= "use Illuminate\Database\Schema\Blueprint;

";

    $out .= "/**
 * Manages database connection and migrations.
 */
";
    $out .= "class DatabaseConnection {
";

    $out .= "    /**
     * Initializes the database connection based on environment and CLI flags.
     * @param bool \$ephemeral If true, forces an in-memory SQLite database.
     * @return Capsule
     */
";
    $out .= "    public static function connect(bool \$ephemeral = false): Capsule {
";
    $out .= "        \$capsule = new Capsule;
";
    $out .= "        \$dbUrl = getenv('DATABASE_URL');
";
    $out .= "        if (\$ephemeral || !\$dbUrl) {
";
    $out .= "            \$capsule->addConnection([
";
    $out .= "                'driver'   => 'sqlite',
";
    $out .= "                'database' => ':memory:',
";
    $out .= "                'prefix'   => '',
";
    $out .= "            ]);
";
    $out .= "        } else {
";
    $out .= "            \$parsed = is_string(\$dbUrl) ? parse_url(\$dbUrl) : false;
";
    $out .= "            \$parsed = is_array(\$parsed) ? \$parsed : [];
";
    $out .= "            \$scheme = (string) (\$parsed['scheme'] ?? 'sqlite');
";
    $out .= "            \$capsule->addConnection([
";
    $out .= "                'driver'   => \$scheme === 'postgres' ? 'pgsql' : \$scheme,
";
    $out .= "                'host'     => \$parsed['host'] ?? '127.0.0.1',
";
    $out .= "                'port'     => \$parsed['port'] ?? 5432,
";
    $out .= "                'database' => ltrim((string) (\$parsed['path'] ?? ''), '/'),
";
    $out .= "                'username' => \$parsed['user'] ?? 'root',
";
    $out .= "                'password' => \$parsed['pass'] ?? '',
";
    $out .= "                'charset'  => 'utf8',
";
    $out .= "                'prefix'   => '',
";
    $out .= "            ]);
";
    $out .= "        }
";
    $out .= "        \$capsule->setAsGlobal();
";
    $out .= "        \$capsule->bootEloquent();
";
    $out .= "        return \$capsule;
";
    $out .= "    }

";

    $out .= "    /**
     * Runs schema migrations for all defined entities.
     * @return void
     */
";
    $out .= "    public static function migrate(): void {
";
    foreach ($schemas as $name => $schema) {
        $tableName = strtolower((string) $name) . 's';
        $exportedTable = var_export($tableName, true);
        $out .= "        if (!Capsule::schema()->hasTable({$exportedTable})) {
";
        $out .= "            Capsule::schema()->create({$exportedTable}, function (Blueprint \$table) {
";
        $out .= "                \$table->id();
";
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propDef) {
                if ($propName === 'id') {
                    continue;
                }
                $type = is_array($propDef) ? (string) ($propDef['type'] ?? 'string') : 'string';
                $format = is_array($propDef) ? (string) ($propDef['format'] ?? '') : '';
                $dbType = 'string';
                if ($type === 'integer') {
                    $dbType = 'integer';
                } elseif ($type === 'boolean') {
                    $dbType = 'boolean';
                } elseif ($type === 'number') {
                    $dbType = 'float';
                } elseif ($type === 'array' || $type === 'object') {
                    $dbType = 'json';
                } elseif ($type === 'string' && $format === 'date-time') {
                    $dbType = 'dateTime';
                } elseif ($type === 'string' && $format === 'text') {
                    $dbType = 'text';
                }
                $exportedProp = var_export((string) $propName, true);
                $out .= "                \$table->{$dbType}({$exportedProp})->nullable();
";
            }
        }
        $out .= "                \$table->timestamps();
";
        $out .= "            });
";
        $out .= "        }
";
    }
    $out .= "    }
";

    $out .= "}
";
    return $out;
}
