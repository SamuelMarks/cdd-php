<?php

declare(strict_types=1);

namespace Cdd\ServerCli;

/**
 * Emits the Server CLI runner class.
 *
 * @return string The generated ServerRunner class
 */
function emit(): string
{
    $out = "<?php\n\n/**\n * Auto-generated Server Runner\n */\n\n";
    $out .= "class ServerRunner {\n";
    $out .= "    public bool \$isEphemeral = false;\n";
    $out .= "    public bool \$shouldSeed = false;\n";
    $out .= "    public bool \$isStubMode = false;\n\n";

    $out .= "    /**\n     * Parses CLI arguments and determines server mode.\n     * --ephemeral: Uses an in-memory database\n     * --seed: Runs the data seeder\n     * @param array \$argv CLI arguments\n     * @return void\n     */\n";
    $out .= "    public function parseArgs(array \$argv): void {\n";
    $out .= "        \$this->isEphemeral = in_array('--ephemeral', \$argv, true);\n";
    $out .= "        \$this->shouldSeed = in_array('--seed', \$argv, true);\n";
    $out .= "        \$dbUrl = getenv('DATABASE_URL');\n";
    $out .= "        \$this->isStubMode = !\$this->isEphemeral && !\$dbUrl;\n";
    $out .= "    }\n\n";

    $out .= "    /**\n     * Executes the server startup lifecycle.\n     * @param array \$argv CLI arguments\n     * @return void\n     */\n";
    $out .= "    public function run(array \$argv): void {\n";
    $out .= "        \$this->parseArgs(\$argv);\n";
    $out .= "        if (!\$this->isStubMode) {\n";
    $out .= "            if (class_exists('DatabaseConnection')) {\n";
    $out .= "                DatabaseConnection::connect(\$this->isEphemeral);\n";
    $out .= "                DatabaseConnection::migrate();\n";
    $out .= "            }\n";
    $out .= "            if (\$this->shouldSeed && class_exists('DatabaseSeeder')) {\n";
    $out .= "                \$seeder = new DatabaseSeeder();\n";
    $out .= "                \$seeder->seed();\n";
    $out .= "            }\n";
    $out .= "        }\n";
    $out .= "    }\n";
    $out .= "}\n";

    return $out;
}
