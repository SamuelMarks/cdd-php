<?php

/**
 * Emits the ServerRunner CLI class for generated server projects.
 *
 * @package Cdd\ServerCli
 */

declare(strict_types=1);

namespace Cdd\ServerCli;

/**
 * Emits the Server CLI runner class.
 *
 * @param string $namespace Optional PHP namespace for the generated ServerRunner class.
 * @return string The generated ServerRunner class.
 */
function emit(string $namespace = 'Api'): string
{
    $out = "<?php

/**
 * Auto-generated Server Runner
 */

declare(strict_types=1);

";
    if ($namespace !== '') {
        $out .= "namespace {$namespace};

";
        $out .= "use Api\Database\DatabaseConnection;
";
        $out .= "use Api\Seeder\DatabaseSeeder;

";
    }
    $out .= "class ServerRunner {
";
    $out .= "    public bool \$isEphemeral = false;
";
    $out .= "    public bool \$shouldSeed = false;
";
    $out .= "    public bool \$isStubMode = false;

";

    $out .= "    /**
     * Parses CLI arguments and determines server mode.
     * --ephemeral: Uses an in-memory database
     * --seed: Runs the data seeder
     * @param array<int, string> \$argv CLI arguments
     * @return void
     */
";
    $out .= "    public function parseArgs(array \$argv): void {
";
    $out .= "        \$this->isEphemeral = in_array('--ephemeral', \$argv, true);
";
    $out .= "        \$this->shouldSeed = in_array('--seed', \$argv, true);
";
    $out .= "        \$dbUrl = getenv('DATABASE_URL');
";
    $out .= "        \$this->isStubMode = !\$this->isEphemeral && !\$dbUrl;
";
    $out .= "    }

";

    $out .= "    /**
     * Executes the server startup lifecycle.
     * @param array<int, string> \$argv CLI arguments
     * @return void
     */
";
    $out .= "    public function run(array \$argv): void {
";
    $out .= "        \$this->parseArgs(\$argv);
";
    $out .= "        if (!\$this->isStubMode) {
";
    $out .= "            if (class_exists(DatabaseConnection::class)) {
";
    $out .= "                DatabaseConnection::connect(\$this->isEphemeral);
";
    $out .= "                DatabaseConnection::migrate();
";
    $out .= "            } elseif (class_exists('DatabaseConnection')) {
";
    $out .= "                \DatabaseConnection::connect(\$this->isEphemeral);
";
    $out .= "                \DatabaseConnection::migrate();
";
    $out .= "            }
";
    $out .= "            if (\$this->shouldSeed) {
";
    $out .= "                if (class_exists(DatabaseSeeder::class)) {
";
    $out .= "                    \$seeder = new DatabaseSeeder();
";
    $out .= "                    \$seeder->seed();
";
    $out .= "                } elseif (class_exists('DatabaseSeeder')) {
";
    $out .= "                    \$seeder = new \DatabaseSeeder();
";
    $out .= "                    \$seeder->seed();
";
    $out .= "                }
";
    $out .= "            }
";
    $out .= "        }
";
    $out .= "    }
";
    $out .= "}
";

    return $out;
}
