<?php

/**
 * Emits FakerPHP database seeder logic with foreign-key topological sorting.
 *
 * @package Cdd\Seeder
 */

declare(strict_types=1);

namespace Cdd\Seeder;

/**
 * Emits Seeder logic using FakerPHP.
 *
 * @param array<string, array<string, mixed>> $schemas The OpenAPI schemas.
 * @param string $namespace Optional PHP namespace for the generated DatabaseSeeder class.
 * @return string The generated DatabaseSeeder PHP code.
 */
function emit(array $schemas, string $namespace = ''): string
{
    $out = "<?php

/**
 * Auto-generated Fake Data Seeder
 * Manages referential integrity by caching IDs in an Entity Pool.
 */

declare(strict_types=1);

";
    $bs = chr(92);
    if ($namespace !== '') {
        $out .= "namespace {$namespace};

";
        $out .= "use Api{$bs}Database{$bs}DatabaseConnection;
";
        foreach (array_keys($schemas) as $schemaName) {
            $out .= "use Api{$bs}Models{$bs}{$schemaName};
";
        }
    }
    $out .= "use Faker{$bs}Factory as FakerFactory;
";
    $out .= "use Illuminate{$bs}Database{$bs}Capsule{$bs}Manager as Capsule;

";

    $out .= "/**
 * Seeder class to populate the database with fake data.
 */
";
    $out .= "class DatabaseSeeder {
";
    $out .= "    private {$bs}Faker{$bs}Generator \$faker;
";
    $out .= "    private array \$entityPool = [];

";

    $out .= "    public function __construct() {
";
    $out .= "        \$this->faker = FakerFactory::create('en_US');
";
    $out .= "    }

";

    // Topological sort heuristic with self-reference / cycle guard
    $deps = [];
    $names = array_keys($schemas);
    foreach ($schemas as $name => $schema) {
        $deps[$name] = [];
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propDef) {
                if (preg_match('/^([a-z_]+)_id$/i', (string) $propName, $matches)) {
                    $potentialParent = str_replace('_', '', ucwords($matches[1], '_'));
                    if ($potentialParent !== $name && in_array($potentialParent, $names, true)) {
                        $deps[$name][] = $potentialParent;
                    }
                }
            }
        }
    }

    $sorted = [];
    $visited = [];
    $visit = function (string $node) use (&$visit, &$visited, &$sorted, $deps): void {
        if (!isset($visited[$node])) {
            $visited[$node] = true;
            foreach ($deps[$node] as $dep) {
                $visit($dep);
            }
            $sorted[] = $node;
        }
    };
    foreach ($names as $name) {
        $visit((string) $name);
    }

    $out .= "    /**
     * Runs the seeder logic to populate the DB.
     */
";
    $out .= "    public function seed(): void {
";
    $out .= "        Capsule::transaction(function() {
";

    foreach ($sorted as $name) {
        $schema = $schemas[$name];
        $count = !empty($deps[$name]) ? 50 : 10;
        $exportedName = var_export((string) $name, true);

        $out .= "            // Seed {$name}
";
        $out .= "            \$this->entityPool[{$exportedName}] = [];
";
        $out .= "            for (\$i = 0; \$i < {$count}; \$i++) {
";
        $out .= "                \$data = [
";
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propDef) {
                if ($propName === 'id') {
                    continue;
                }
                $val = "''";
                if (preg_match('/^([a-z_]+)_id$/i', (string) $propName, $matches)) {
                    $parent = str_replace('_', '', ucwords($matches[1], '_'));
                    if (in_array($parent, $names, true)) {
                        $exportedParent = var_export($parent, true);
                        $val = "!empty(\$this->entityPool[{$exportedParent}]) ? \$this->entityPool[{$exportedParent}][array_rand(\$this->entityPool[{$exportedParent}])] : 1";
                    } else {
                        $val = '1';
                    }
                } else {
                    $type = is_array($propDef) ? (string) ($propDef['type'] ?? 'string') : 'string';
                    if ($type === 'string') {
                        if (stripos((string) $propName, 'email') !== false) {
                            $val = "\$this->faker->email()";
                        } elseif (stripos((string) $propName, 'name') !== false) {
                            $val = "\$this->faker->name()";
                        } elseif (stripos((string) $propName, 'phone') !== false) {
                            $val = "\$this->faker->phoneNumber()";
                        } else {
                            $val = "\$this->faker->word()";
                        }
                    } elseif ($type === 'integer') {
                        $val = "\$this->faker->numberBetween(1, 100)";
                    } elseif ($type === 'boolean') {
                        $val = "\$this->faker->boolean()";
                    } elseif ($type === 'number') {
                        $val = "\$this->faker->randomFloat(2, 0, 1000)";
                    }
                }
                $exportedProp = var_export((string) $propName, true);
                $out .= "                    {$exportedProp} => {$val},
";
            }
        }
        $out .= "                ];
";
        $out .= "                \$record = {$name}::create(\$data);
";
        $out .= "                \$this->entityPool[{$exportedName}][] = \$record->id;
";
        $out .= "            }
";
    }

    $out .= "        });
";
    $out .= "    }
";
    $out .= "}
";

    return $out;
}
