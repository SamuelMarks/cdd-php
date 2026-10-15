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
        $out .= "namespace {$namespace};\n\n";
        $out .= "use Api{$bs}Database{$bs}DatabaseConnection;\n";
        foreach (array_keys($schemas) as $schemaName) {
            $out .= "use Api{$bs}Models{$bs}{$schemaName};\n";
        }
    }
    $out .= "use Faker{$bs}Factory as FakerFactory;\n";
    $out .= "use Illuminate{$bs}Database{$bs}Capsule{$bs}Manager as Capsule;\n\n";

    $out .= "/**\n * Seeder class to populate the database with fake data.\n */\n";
    $out .= "class DatabaseSeeder {\n";
    $out .= "    private {$bs}Faker{$bs}Generator \$faker;\n";
    $out .= "    private array \$entityPool = [];\n\n";

    $out .= "    public function __construct() {
";
    $out .= "        \$this->faker = FakerFactory::create('en_US');
";
    $out .= "    }

";

    // Topological sort heuristic
    $deps = [];
    $names = array_keys($schemas);
    foreach ($schemas as $name => $schema) {
        $deps[$name] = [];
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propDef) {
                if (preg_match('/^([a-z_]+)_id$/i', $propName, $matches)) {
                    $potentialParent = str_replace('_', '', ucwords($matches[1], '_'));
                    if (in_array($potentialParent, $names, true)) {
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
        $count = 10; // Default count
        // For children, maybe more
        if (!empty($deps[$name])) {
            $count = 50;
        }

        $out .= "            // Seed {$name}
";
        $out .= "            \$this->entityPool['{$name}'] = [];
";
        $out .= "            for (\$i = 0; \$i < {$count}; \$i++) {
";
        $out .= "                \$data = [
";
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propDef) {
                if ($propName === 'id') {
                    continue;
                }
                $val = "''";
                if (preg_match('/^([a-z_]+)_id$/i', $propName, $matches)) {
                    $parent = str_replace('_', '', ucwords($matches[1], '_'));
                    if (in_array($parent, $names, true)) {
                        $val = "\$this->entityPool['{$parent}'][array_rand(\$this->entityPool['{$parent}'])] ?? 1";
                    } else {
                        $val = "1";
                    }
                } else {
                    $type = $propDef['type'] ?? 'string';
                    if ($type === 'string') {
                        if (stripos($propName, 'email') !== false) {
                            $val = "\$this->faker->email()";
                        } elseif (stripos($propName, 'name') !== false) {
                            $val = "\$this->faker->name()";
                        } elseif (stripos($propName, 'phone') !== false) {
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
                $out .= "                    '{$propName}' => {$val},
";
            }
        }
        $out .= "                ];
";
        $out .= "                \$record = {$name}::create(\$data);
";
        $out .= "                \$this->entityPool['{$name}'][] = \$record->id;
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
