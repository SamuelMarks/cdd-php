<?php

/**
 * Command-line interface and programmatic SDK dispatcher for cdd-php.
 *
 * @package Cdd\Cli
 */

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * CddCli
 *
 * This class implements the command-line interface and programmatic SDK entry points
 * for `cdd-php`.
 */
class CddCli
{
    /**
     * @var resource|null Optional input stream override for testing MCP stdio.
     */
    public static mixed $testInStream = null;

    /**
     * @var resource|null Optional output stream override for testing MCP stdio.
     */
    public static mixed $testOutStream = null;

    /**
     * @var resource|null Optional socket connection override for testing JSON-RPC server.
     */
    public static mixed $testSocketConn = null;

    /**
     * Configure custom I/O and socket test streams.
     *
     * @param resource|null $inStream Input stream override.
     * @param resource|null $outStream Output stream override.
     * @param resource|null $socketConn Socket connection override.
     * @return void
     */
    public static function setStreams(mixed $inStream = null, mixed $outStream = null, mixed $socketConn = null): void
    {
        self::$testInStream = $inStream;
        self::$testOutStream = $outStream;
        self::$testSocketConn = $socketConn;
    }

    /**
     * Programmatic helper for `from_openapi`.
     *
     * @param CddConfig|array<int, string> $args Configuration struct or CLI argument array.
     * @return int Exit status code.
     */
    public static function generate_from_openapi(CddConfig|array $args): int
    {
        if ($args instanceof CddConfig) {
            $args->command = 'from_openapi';
            return self::run($args->toArgs());
        }
        return self::run(array_merge(['cdd-php', 'from_openapi'], $args));
    }

    /**
     * Programmatic helper for `to_openapi`.
     *
     * @param CddConfig|array<int, string> $args Configuration struct or CLI argument array.
     * @return int Exit status code.
     */
    public static function generate_to_openapi(CddConfig|array $args): int
    {
        if ($args instanceof CddConfig) {
            $args->command = 'to_openapi';
            return self::run($args->toArgs());
        }
        return self::run(array_merge(['cdd-php', 'to_openapi'], $args));
    }

    /**
     * Programmatic helper for `to_docs_json`.
     *
     * @param CddConfig|array<int, string> $args Configuration struct or CLI argument array.
     * @return int Exit status code.
     */
    public static function generate_docs_json(CddConfig|array $args): int
    {
        if ($args instanceof CddConfig) {
            $args->command = 'to_docs_json';
            return self::run($args->toArgs());
        }
        return self::run(array_merge(['cdd-php', 'to_docs_json'], $args));
    }

    /**
     * Programmatic helper for `serve_json_rpc`.
     *
     * @param CddConfig|array<int, string> $args Configuration struct or CLI argument array.
     * @return int Exit status code.
     */
    public static function serve_json_rpc(CddConfig|array $args): int
    {
        if ($args instanceof CddConfig) {
            $args->command = 'serve_json_rpc';
            return self::run($args->toArgs());
        }
        return self::run(array_merge(['cdd-php', 'serve_json_rpc'], $args));
    }

    /**
     * Programmatic helper for `from_arazzo`.
     *
     * @param CddConfig|array<int, string> $args Configuration struct or CLI argument array.
     * @return int Exit status code.
     */
    public static function generate_from_arazzo(CddConfig|array $args): int
    {
        if ($args instanceof CddConfig) {
            $args->command = 'from_arazzo';
            return self::run($args->toArgs());
        }
        return self::run(array_merge(['cdd-php', 'from_arazzo'], $args));
    }

    /**
     * Programmatic helper for `to_arazzo`.
     *
     * @param CddConfig|array<int, string> $args Configuration struct or CLI argument array.
     * @return int Exit status code.
     */
    public static function generate_to_arazzo(CddConfig|array $args): int
    {
        if ($args instanceof CddConfig) {
            $args->command = 'to_arazzo';
            return self::run($args->toArgs());
        }
        return self::run(array_merge(['cdd-php', 'to_arazzo'], $args));
    }

    /**
     * Initiates an LLM sampling request over MCP stdio.
     *
     * @param array<int, array<string, mixed>> $messages Conversation messages.
     * @param int $maxTokens Maximum tokens to sample.
     * @param string $systemPrompt Optional system prompt.
     * @return mixed Sampling result or null on EOF.
     * @throws \Exception If the MCP client returns an error response.
     */
    public static function sample_llm(array $messages, int $maxTokens = 100, string $systemPrompt = ''): mixed
    {
        return McpServer::sampleLlm($messages, $maxTokens, $systemPrompt);
    }

    /**
     * Processes a single HTTP JSON-RPC connection stream.
     *
     * @param resource $conn The accepted socket stream connection.
     * @param int $chunkSize Read buffer chunk size in bytes.
     * @return string The formatted HTTP response written to the connection.
     */
    public static function handleJsonRpcConnection(mixed $conn, int $chunkSize = 8192): string
    {
        return JsonRpcHttpServer::handleConnection($conn, $chunkSize);
    }

    /**
     * Prints top-level or subcommand-specific CLI help text.
     *
     * @param string $command The primary subcommand name (or empty for top-level help).
     * @param string $subtarget Optional secondary subtarget (e.g., `to_sdk`, `to_sdk_cli`, `to_server`).
     * @return int Exit status code (`0` if handled, `-1` if unknown command).
     */
    public static function printHelp(string $command = '', string $subtarget = ''): int
    {
        if ($command === '' || $command === '--help' || $command === '-h') {
            echo "cdd-php CLI
";
            echo "Usage:
";
            echo "  cdd-php [subcommand] [options]

";
            echo "Subcommands:
";
            echo "  from_openapi    Generate code from an OpenAPI specification.
";
            echo "  to_openapi      Generate an OpenAPI specification from source code.
";
            echo "  from_arazzo     Generate code from an Arazzo workflow specification.
";
            echo "  to_arazzo       Generate an Arazzo specification from workflow source code.
";
            echo "  to_docs_json    Generate documentation JSON from an OpenAPI specification.
";
            echo "  serve_json_rpc  Start a JSON-RPC server.
";
            echo "  sync            Synchronize codebase with OpenAPI specification.
";
            echo "  mcp             Run the Model Context Protocol (MCP) server.

";
            echo "Options:
";
            echo "  --help, -h      Show this help message and exit.
";
            echo "  --version, -v   Show version information and exit.

";
            echo "Examples:
";
            echo "  cdd-php to_openapi -i path/to/code -o openapi.json
";
            echo "  cdd-php serve_json_rpc [--port|-p 8080] [--listen|-l 127.0.0.1]
";
            echo "  cdd-php to_docs_json --no-imports --no-wrapping -i spec.json -o docs.json
";
            echo "  cdd-php from_openapi to_sdk_cli -i spec.json -o target_directory [--no-github-actions] [--no-installable-package] [--tests] [--mcp]
";
            echo "  cdd-php from_openapi to_sdk -i spec.json -o target_directory [--no-github-actions] [--no-installable-package] [--tests] [--mcp]
";
            echo "  cdd-php from_openapi to_server -i spec.json -o target_directory
";
            echo "  cdd-php sync -i directory -t class
";
            return 0;
        }
        if ($command === 'from_openapi' || $command === 'emit') {
            if (in_array($subtarget, ['to_sdk_cli', 'to_sdk', 'to_server'], true)) {
                echo "Usage: cdd-php from_openapi $subtarget [options]

";
                echo "Options:
";
                echo "  -i, --input <spec>              Path to the OpenAPI specification file
";
                echo "  -d, --input-dir <dir>           Path to a directory containing OpenAPI specifications
";
                echo "  -o, --output <dir>              Destination path for generation
";
                echo "  --no-github-actions             Disable GitHub Actions generation
";
                echo "  --no-installable-package        Disable Composer package generation
";
                echo "  --tests                         Generate integration and unit tests
";
                echo "  --mcp                           Generate Model Context Protocol (MCP) helpers
";
                echo "  --help, -h                      Show this help message
";
                return 0;
            }
            echo "Usage: cdd-php from_openapi <target> [options]

";
            echo "Targets:
";
            echo "  to_sdk_cli      Generate a CLI client SDK
";
            echo "  to_sdk          Generate a client SDK
";
            echo "  to_server       Generate a server stub

";
            echo "Options:
";
            echo "  -i, --input <spec>              Path to the OpenAPI specification file
";
            echo "  -d, --input-dir <dir>           Path to a directory containing OpenAPI specifications
";
            echo "  -o, --output <dir>              Destination path for generation
";
            echo "  --no-github-actions             Disable GitHub Actions generation
";
            echo "  --no-installable-package        Disable Composer package generation
";
            echo "  --tests                         Generate integration and unit tests
";
            echo "  --mcp                           Generate Model Context Protocol (MCP) helpers
";
            echo "  --help, -h                      Show this help message
";
            return 0;
        }
        if ($command === 'to_openapi') {
            echo "Usage: cdd-php to_openapi [options]

";
            echo "Options:
";
            echo "  -i, --input <path>    Path to the source code directory or file to parse
";
            echo "  -o, --output <path>   Destination file for the OpenAPI specification
";
            echo "  --help, -h            Show this help message
";
            return 0;
        }
        if ($command === 'to_docs_json') {
            echo "Usage: cdd-php to_docs_json [options]

";
            echo "Options:
";
            echo "  -i, --input <spec>    Path to the OpenAPI specification file
";
            echo "  -o, --output <path>   Destination file for the documentation JSON
";
            echo "  --no-imports          Disable import statements in the generated documentation
";
            echo "  --no-wrapping         Disable line wrapping in the generated documentation
";
            echo "  --help, -h            Show this help message
";
            return 0;
        }
        if ($command === 'serve_json_rpc') {
            echo "Usage: cdd-php serve_json_rpc [options]

";
            echo "Options:
";
            echo "  -p, --port <port>     Port to listen on (e.g., 8080)
";
            echo "  -l, --listen <addr>   Address to bind to (e.g., 127.0.0.1)
";
            echo "  --timeout <seconds>   Socket accept timeout in seconds
";
            echo "  --help, -h            Show this help message
";
            return 0;
        }
        if ($command === 'sync') {
            echo "Usage: cdd-php sync [options]

";
            echo "Options:
";
            echo "  -i, --input <directory> Target directory to synchronize
";
            echo "  -t, --truth <source>  Source of truth for synchronization (e.g., 'class', 'spec')
";
            echo "  --help, -h            Show this help message
";
            return 0;
        }
        if ($command === 'mcp') {
            echo "Usage: cdd-php mcp

";
            echo "Run the generator as an MCP server over stdio.
";
            return 0;
        }
        if ($command === 'from_arazzo') {
            echo "Usage: cdd-php from_arazzo <target> [options]

";
            echo "Targets:
";
            echo "  to_sdk_cli      Generate a workflow client SDK and CLI runner
";
            echo "  to_sdk          Generate a workflow client SDK
";
            echo "  to_server       Generate a server workflow engine and E2E workflow tests

";
            echo "Options:
";
            echo "  -i, --input <spec>    Path to the Arazzo specification file
";
            echo "  -d, --input-dir <dir> Path to a directory containing Arazzo specifications
";
            echo "  -o, --output <dir>    Destination path for generation
";
            echo "  --no-github-actions   Disable GitHub Actions generation
";
            echo "  --no-installable-package Disable Composer package generation
";
            echo "  --help, -h            Show this help message
";
            return 0;
        }
        if ($command === 'to_arazzo') {
            echo "Usage: cdd-php to_arazzo [options]

";
            echo "Options:
";
            echo "  -i, --input <path>    Path to the workflow source file or directory to parse
";
            echo "  -o, --output <path>   Destination file for the Arazzo specification
";
            echo "  --help, -h            Show this help message
";
            return 0;
        }
        return -1;
    }

    /**
     * Runs the Model Context Protocol (MCP) JSON-RPC server over stdio.
     *
     * @return int Exit status code.
     */
    public static function runMcpServer(): int
    {
        return McpServer::run();
    }

    /**
     * Runs the TCP HTTP JSON-RPC server (`serve_json_rpc`).
     *
     * @param array<int, string> $argv CLI arguments.
     * @return int Exit status code.
     */
    public static function runServeJsonRpc(array $argv): int
    {
        return JsonRpcHttpServer::serve($argv);
    }

    /**
     * Runs the bidirectional `sync` command.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runSync(array $argv, callable $resolvePath): int
    {
        return ProjectSynchronizer::runSync($argv, $resolvePath);
    }

    /**
     * Runs the `to_openapi` subcommand.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runToOpenApi(array $argv, callable $resolvePath): int
    {
        return ProjectSynchronizer::runToOpenApi($argv, $resolvePath);
    }

    /**
     * Runs the `from_openapi` (or `emit`) subcommand.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runFromOpenApi(array $argv, callable $resolvePath): int
    {
        $argc = count($argv);
        $subcommand = 'to_sdk';
        $file = '';
        $inputDir = '';
        $dir = (string) getcwd();

        $noGithubActions = false;
        $noInstallablePackage = false;
        $tests = false;
        $mcp = false;

        $newArgv = [];
        for ($k = 0; $k < $argc; $k++) {
            if ($argv[$k] === '--no-github-actions') {
                $noGithubActions = true;
            } elseif ($argv[$k] === '--no-installable-package') {
                $noInstallablePackage = true;
            } elseif ($argv[$k] === '--tests') {
                $tests = true;
            } elseif ($argv[$k] === '--mcp') {
                $mcp = true;
            } else {
                $newArgv[] = $argv[$k];
            }
        }
        $argv = $newArgv;
        $argc = count($argv);

        $i = 2;
        if (isset($argv[$i]) && in_array($argv[$i], ['to_sdk_cli', 'to_sdk', 'to_server'], true)) {
            $subcommand = $argv[$i];
            $i++;
        }

        for (; $i < $argc; $i++) {
            if (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                $file = $resolvePath($argv[$i + 1]);
                $i++;
            } elseif (($argv[$i] === '-d' || $argv[$i] === '--input-dir') && isset($argv[$i + 1])) {
                $inputDir = $resolvePath($argv[$i + 1]);
                $i++;
            } elseif (($argv[$i] === '-o' || $argv[$i] === '--output') && isset($argv[$i + 1])) {
                $dir = $resolvePath($argv[$i + 1]);
                $i++;
            } else {
                $dir = $resolvePath($argv[$i]);
            }
        }

        if ($file === '' && $inputDir === '') {
            echo "Error: Spec file or input dir not provided.
";
            return 1;
        }

        $specFile = $file;
        if ($specFile !== '') {
            if (!file_exists($specFile)) {
                echo "Error: Spec file not found ($specFile).
";
                return 1;
            }
        } else {
            if (!is_dir($inputDir)) {
                echo "Error: Input directory not found.
";
                return 1;
            }
            $files = glob("$inputDir/*.json");
            if (empty($files)) {
                echo "Error: No .json files found in input dir.
";
                return 1;
            }
            $specFile = $files[0];
        }

        $spec = \Cdd\Openapi\parse((string) file_get_contents($specFile));
        \Cdd\Openapi\emit($spec, $dir, [
            'no_github_actions' => $noGithubActions,
            'no_installable_package' => $noInstallablePackage,
            'tests' => $tests,
            'mcp' => $mcp,
            'subcommand' => $subcommand,
        ]);
        if ($subcommand === 'to_sdk_cli') {
            $cliCode = \Cdd\Cli\emit($spec['paths'] ?? []);
            file_put_contents("$dir/src/api_cli.php", $cliCode);
        }

        echo "Emitted code to $dir successfully.
";
        return 0;
    }

    /**
     * Runs the `to_docs_json` subcommand.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runToDocsJson(array $argv, callable $resolvePath): int
    {
        return DocsJsonGenerator::run($argv, $resolvePath);
    }

    /**
     * Runs the `from_arazzo` subcommand to compile an Arazzo specification into PHP SDK, CLI, or Server artifacts.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runFromArazzo(array $argv, callable $resolvePath): int
    {
        $argc = count($argv);
        $subcommand = 'to_sdk';
        $file = '';
        $inputDir = '';
        $dir = (string) getcwd();
        $noGithubActions = false;
        $noInstallablePackage = false;

        $i = 2;
        if (isset($argv[$i]) && in_array($argv[$i], ['to_sdk_cli', 'to_sdk', 'to_server', 'all'], true)) {
            $subcommand = $argv[$i];
            $i++;
        }

        for (; $i < $argc; $i++) {
            if ($argv[$i] === '--no-github-actions') {
                $noGithubActions = true;
            } elseif ($argv[$i] === '--no-installable-package') {
                $noInstallablePackage = true;
            } elseif (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                $file = $resolvePath($argv[++$i]);
            } elseif (($argv[$i] === '-d' || $argv[$i] === '--input-dir') && isset($argv[$i + 1])) {
                $inputDir = $resolvePath($argv[++$i]);
            } elseif (($argv[$i] === '-o' || $argv[$i] === '--output') && isset($argv[$i + 1])) {
                $dir = $resolvePath($argv[++$i]);
            }
        }

        if ($file === '' && $inputDir !== '' && is_dir($inputDir)) {
            $candidates = glob("$inputDir/*.json");
            if (!empty($candidates)) {
                $file = $candidates[0];
            }
        }

        if ($file === '' || !file_exists($file)) {
            echo "Error: Arazzo spec file not found.
";
            return 1;
        }

        $spec = \Cdd\Arazzo\parse((string) file_get_contents($file));
        \Cdd\Arazzo\emit($spec, $dir, [
            'subcommand' => $subcommand,
            'no_github_actions' => $noGithubActions,
            'no_installable_package' => $noInstallablePackage,
        ]);

        echo "Emitted Arazzo code to $dir successfully.
";
        return 0;
    }

    /**
     * Runs the `to_arazzo` subcommand to extract an Arazzo 1.1.0 specification from PHP workflow code.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runToArazzo(array $argv, callable $resolvePath): int
    {
        $argc = count($argv);
        $inputPath = '';
        $outFile = '';

        for ($i = 2; $i < $argc; $i++) {
            if (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                $inputPath = $resolvePath($argv[++$i]);
            } elseif (($argv[$i] === '-o' || $argv[$i] === '--output') && isset($argv[$i + 1])) {
                $outFile = $resolvePath($argv[++$i]);
            } elseif ($inputPath === '') {
                $inputPath = $resolvePath($argv[$i]);
            }
        }

        if ($inputPath === '' || !file_exists($inputPath)) {
            echo "Error: Input file or directory not found.
";
            return 1;
        }

        $arazzo = [];
        if (is_dir($inputPath)) {
            if (file_exists("$inputPath/src/ApiWorkflows.php")) {
                $arazzo = \Cdd\ArazzoClient\parse((string) file_get_contents("$inputPath/src/ApiWorkflows.php"));
            } elseif (file_exists("$inputPath/src/arazzo_cli.php")) {
                $arazzo = \Cdd\ArazzoCli\parse((string) file_get_contents("$inputPath/src/arazzo_cli.php"));
            } elseif (file_exists("$inputPath/src/WorkflowEngine.php")) {
                $arazzo = \Cdd\ArazzoServer\parse((string) file_get_contents("$inputPath/src/WorkflowEngine.php"));
            } elseif (file_exists("$inputPath/tests/Workflows/ArazzoWorkflowTest.php")) {
                $arazzo = \Cdd\ArazzoServer\parse((string) file_get_contents("$inputPath/tests/Workflows/ArazzoWorkflowTest.php"));
            }
        } else {
            $raw = (string) file_get_contents($inputPath);
            if (str_contains($inputPath, 'arazzo_cli.php')) {
                $arazzo = \Cdd\ArazzoCli\parse($raw);
            } elseif (str_contains($inputPath, 'WorkflowTest') || str_contains($inputPath, 'WorkflowEngine')) {
                $arazzo = \Cdd\ArazzoServer\parse($raw);
            } else {
                $arazzo = \Cdd\ArazzoClient\parse($raw);
            }
        }

        $outStr = \Cdd\Arazzo\emit($arazzo) . "
";
        if ($outFile !== '') {
            file_put_contents($outFile, $outStr);
            echo "Emitted Arazzo to $outFile
";
        } else {
            echo $outStr;
        }
        return 0;
    }

    /**
     * Runs the CLI application.
     *
     * @param array<int, string> $argv The command line arguments
     * @return int The exit status code (0 for success, non-zero for failure)
     */
    public static function run(array $argv): int
    {
        $originalPwd = (string) (getenv('PWD') ?: getcwd());
        $resolvePath = static function (string $path) use ($originalPwd): string {
            if ($path === '' || $path[0] === '/') {
                return $path;
            }
            return $originalPwd . '/' . $path;
        };

        foreach ($_ENV as $k => $v) {
            if (strpos((string) $k, 'CDD_') === 0) {
                $argName = '--' . strtolower(str_replace('_', '-', substr((string) $k, 4)));
                if (!in_array($argName, $argv, true)) {
                    $argv[] = $argName;
                    $argv[] = (string) $v;
                }
            }
        }
        $command = $argv[1] ?? '';

        if ($command === '--version' || $command === '-v') {
            echo "0.0.4
";
            return 0;
        }

        if ($command === '--help' || $command === '-h' || $command === '') {
            return self::printHelp('');
        }

        if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
            $helpExit = self::printHelp($command, $argv[2] ?? '');
            if ($helpExit === 0) {
                return 0;
            }
        }

        return match ($command) {
            'mcp' => self::runMcpServer(),
            'serve_json_rpc' => self::runServeJsonRpc($argv),
            'sync' => self::runSync($argv, $resolvePath),
            'to_openapi' => self::runToOpenApi($argv, $resolvePath),
            'from_openapi', 'emit' => self::runFromOpenApi($argv, $resolvePath),
            'from_arazzo' => self::runFromArazzo($argv, $resolvePath),
            'to_arazzo' => self::runToArazzo($argv, $resolvePath),
            'to_docs_json' => self::runToDocsJson($argv, $resolvePath),
            default => (static function () use ($command): int {
                fwrite(STDERR, "Error: Unknown or incomplete command: $command
");
                return 1;
            })(),
        };
    }
}
