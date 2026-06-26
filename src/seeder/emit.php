<?php

declare(strict_types=1);

namespace Cdd\Seeder;

/**
 * Emits Seeder logic using FakerPHP.
 *
 * @param array $schemas The OpenAPI schemas
 * @return string
 */
function emit(array $schemas): string
{
    $out = "<?php\n\n/**\n * Auto-generated Fake Data Seeder\n * Manages referential integrity by caching IDs in an Entity Pool.\n */\n\n";
    $out .= "use Faker\\Factory as FakerFactory;\n";
    $out .= "use Illuminate\\Database\\Capsule\\Manager as Capsule;\n\n";

    $out .= "/**\n * Seeder class to populate the database with fake data.\n */\n";
    $out .= "class DatabaseSeeder {\n";
    $out .= "    private \\Faker\\Generator \$faker;\n";
    $out .= "    private array \$entityPool = [];\n\n";

    $out .= "    public function __construct() {\n";
    $out .= "        \$this->faker = FakerFactory::create('en_US');\n";
    $out .= "    }\n\n";

    // Topological sort heuristic
    $deps = [];
    $names = array_keys($schemas);
    foreach ($schemas as $name => $schema) {
        $deps[$name] = [];
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propDef) {
                if (preg_match('/^([a-z_]+)_id$/i', $propName, $matches)) {
                    $potentialParent = str_replace('_', '', ucwords($matches[1], '_'));
                    if (in_array($potentialParent, $names)) {
                        $deps[$name][] = $potentialParent;
                    }
                }
            }
        }
    }

    $sorted = [];
    $visited = [];
    $visit = function ($node) use (&$visit, &$visited, &$sorted, $deps) {
        if (!isset($visited[$node])) {
            $visited[$node] = true;
            foreach ($deps[$node] as $dep) {
                $visit($dep);
            }
            $sorted[] = $node;
        }
    };
    foreach ($names as $name) {
        $visit($name);
    }

    $out .= "    /**\n     * Runs the seeder logic to populate the DB.\n     */\n";
    $out .= "    public function seed(): void {\n";
    $out .= "        Capsule::transaction(function() {\n";

    foreach ($sorted as $name) {
        $schema = $schemas[$name];
        $count = 10; // Default count
        // For children, maybe more
        if (!empty($deps[$name])) {
            $count = 50;
        }

        $out .= "            // Seed {$name}\n";
        $out .= "            \$this->entityPool['{$name}'] = [];\n";
        $out .= "            for (\$i = 0; \$i < {$count}; \$i++) {\n";
        $out .= "                \$data = [\n";
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propDef) {
                if ($propName === 'id') {
                    continue;
                }
                $val = "''";
                if (preg_match('/^([a-z_]+)_id$/i', $propName, $matches)) {
                    $parent = str_replace('_', '', ucwords($matches[1], '_'));
                    if (in_array($parent, $names)) {
                        $val = "\$this->entityPool['{$parent}'][array_rand(\$this->entityPool['{$parent}'])] ?? 1";
                    } else {
                        /*cov_ignore*/ $val = "1";
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
                $out .= "                    '{$propName}' => {$val},\n";
            }
        }
        $out .= "                ];\n";
        $out .= "                \$record = {$name}::create(\$data);\n";
        $out .= "                \$this->entityPool['{$name}'][] = \$record->id;\n";
        $out .= "            }\n";
    }

    $out .= "        });\n";
    $out .= "    }\n";
    $out .= "}\n";

    return $out;
}
