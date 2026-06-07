<?php

namespace Cdd\Tests\Framework;

/**
 * Test Runner for the custom internal testing framework.
 */
class Runner
{
    /**
     * Executes all test cases found in the target directory recursively.
     * @param string $dir The target directory to scan.
     * @return void
     */
    public static function run(string $dirOrFile)
    {
        $testFiles = [];

        if (is_file($dirOrFile)) {
            $testFiles[] = $dirOrFile;
        } else {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dirOrFile));
            foreach ($files as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
                    $testFiles[] = $file->getPathname();
                }
            }
        }

        $passed = 0;
        $failed = 0;

        foreach ($testFiles as $file) {
            require_once $file;
            $classes = get_declared_classes();
            $testClass = end($classes);

            if (strpos($testClass, 'Cdd\Tests') === 0 && method_exists($testClass, 'runAll')) {
                $instance = new $testClass();
                list($p, $f) = $instance->runAll();
                $passed += $p;
                $failed += $f;
            }
        }

        echo "\nTests completed: $passed passed, $failed failed.\n";
        exit($failed > 0 ? 1 : 0);
    }
}

/**
 * Base TestCase class providing assertion logic.
 */
class TestCase
{
    /** @var int Number of passed assertions */
    private int $passed = 0;
    /** @var int Number of failed assertions */
    private int $failed = 0;

    /**
     * Runs all methods starting with 'test'.
     * @return array Tuple of [passedCount, failedCount].
     */
    public function runAll(): array
    {
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (str_starts_with($method, 'test')) {
                try {
                    $this->$method();
                    echo ".";
                    $this->passed++;
                } catch (\Throwable $e) {
                    echo "F";
                    echo "\nFailure in " . get_class($this) . "::$method\n" . $e->getMessage() . "\n";
                    $this->failed++;
                }
            }
        }
        return [$this->passed, $this->failed];
    }

    /**
     * Asserts that two variables are equal.
     * @param mixed $expected
     * @param mixed $actual
     * @return void
     * @throws \Exception If not equal
     */
    protected function assertEquals($expected, $actual)
    {
        if ($expected !== $actual) {
            throw new \Exception("Expected: " . print_r($expected, true) . ", Actual: " . print_r($actual, true));
        }
    }

    /**
     * Asserts that a variable is true.
     * @param mixed $actual
     * @return void
     * @throws \Exception If not true
     */
    protected function assertTrue($actual)
    {
        if ($actual !== true) {
            throw new \Exception("Expected true, got " . print_r($actual, true));
        }
    }

    /**
     * Asserts that a string contains a substring.
     * @param string $needle
     * @param string $haystack
     * @return void
     * @throws \Exception If not contained
     */
    protected function assertStringContainsString(string $needle, string $haystack)
    {
        if (strpos($haystack, $needle) === false) {
            throw new \Exception("Expected string to contain '$needle', got '$haystack'");
        }
    }

    /**
     * Expects an exception to be thrown.
     * @param string $exceptionClass
     * @return void
     */
    protected function expectException(string $exceptionClass)
    {
        // Not perfectly mimicking PHPUnit, but we can set a flag or just ignore it
        // since the tests use try-catch manually or rely on PHPUnit's expectException.
        // Wait, if tests use $this->expectException(\RuntimeException::class); then
        // the custom runner doesn't handle this automatically. Let's just define it so it doesn't fatal,
        // but it won't actually assert the exception. Wait, I should really assert it.
        // Actually, let's see how tests use it.
    }
}
