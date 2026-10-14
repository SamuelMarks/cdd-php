<?php

/**
 * Custom internal testing framework Runner and TestCase implementation.
 *
 * @package Cdd\Tests\Framework
 */

declare(strict_types=1);

namespace Cdd\Tests\Framework;

/**
 * Test Runner for the custom internal testing framework.
 */
class Runner
{
    /**
     * Discovers all concrete TestCase subclasses from test files in the given directory or file.
     *
     * @param string $dirOrFile The target directory or file to scan.
     * @return array<int, class-string<TestCase>> List of discovered TestCase class names.
     */
    public static function discoverTestClasses(string $dirOrFile): array
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

        sort($testFiles);
        foreach ($testFiles as $file) {
            require_once $file;
        }

        $testClasses = [];
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, TestCase::class)) {
                $ref = new \ReflectionClass($class);
                if (!$ref->isAbstract()) {
                    $testClasses[] = $class;
                }
            }
        }

        return $testClasses;
    }

    /**
     * Executes all test cases found in the target directory or file recursively.
     *
     * @param string $dirOrFile The target directory or file to scan.
     * @return void
     */
    public static function run(string $dirOrFile): void
    {
        $testClasses = self::discoverTestClasses($dirOrFile);

        $passed = 0;
        $failed = 0;

        foreach ($testClasses as $testClass) {
            /** @var TestCase $instance */
            $instance = new $testClass();
            [$p, $f] = $instance->runAll();
            $passed += $p;
            $failed += $f;
        }

        echo "
Tests completed: $passed passed, $failed failed.
";
        exit($failed > 0 ? 1 : 0);
    }
}

/**
 * Base TestCase class providing assertion and lifecycle logic.
 */
class TestCase
{
    /**
     * @var int Number of passed test methods.
     */
    private int $passed = 0;

    /**
     * @var int Number of failed test methods.
     */
    private int $failed = 0;

    /**
     * @var int Number of assertions executed in the current test method.
     */
    private int $currentAssertions = 0;

    /**
     * @var string|null Expected exception class for the current test method.
     */
    private ?string $expectedExceptionClass = null;

    /**
     * @var string|null Expected exception message substring for the current test method.
     */
    private ?string $expectedExceptionMessage = null;

    /**
     * Lifecycle hook executed before each test method.
     *
     * @return void
     */
    public function setUp(): void
    {
    }

    /**
     * Lifecycle hook executed after each test method.
     *
     * @return void
     */
    public function tearDown(): void
    {
    }

    /**
     * Runs all methods starting with 'test'.
     *
     * @return array{0: int, 1: int} Tuple of [passedCount, failedCount].
     */
    public function runAll(): array
    {
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (str_starts_with($method, 'test')) {
                $this->currentAssertions = 0;
                $this->expectedExceptionClass = null;
                $this->expectedExceptionMessage = null;

                try {
                    $this->setUp();
                    $this->$method();
                    if ($this->expectedExceptionClass !== null) {
                        throw new \Exception("Expected exception {$this->expectedExceptionClass} was not thrown");
                    }
                    if ($this->currentAssertions === 0) {
                        throw new \Exception("Test method completed with 0 assertions");
                    }
                    echo ".";
                    $this->passed++;
                } catch (\Throwable $e) {
                    if ($this->expectedExceptionClass !== null && ($e instanceof $this->expectedExceptionClass)) {
                        if ($this->expectedExceptionMessage !== null && strpos($e->getMessage(), $this->expectedExceptionMessage) === false) {
                            echo "F";
                            echo "
Failure in " . get_class($this) . "::$method
Expected exception message containing '{$this->expectedExceptionMessage}', got '{$e->getMessage()}'
";
                            $this->failed++;
                        } else {
                            echo ".";
                            $this->passed++;
                        }
                    } else {
                        echo "F";
                        echo "
Failure in " . get_class($this) . "::$method
" . $e->getMessage() . "
";
                        $this->failed++;
                    }
                } finally {
                    try {
                        $this->tearDown();
                    } catch (\Throwable $tearDownError) {
                        echo "F";
                        echo "
TearDown failure in " . get_class($this) . "::$method
" . $tearDownError->getMessage() . "
";
                        $this->failed++;
                    }
                }
            }
        }
        return [$this->passed, $this->failed];
    }

    /**
     * Records an assertion execution.
     *
     * @return void
     */
    protected function recordAssertion(): void
    {
        $this->currentAssertions++;
    }

    /**
     * Returns the number of assertions executed in the current test method.
     *
     * @return int The assertion count.
     */
    public function getAssertionCount(): int
    {
        return $this->currentAssertions;
    }

    /**
     * Asserts that two variables are strictly equal.
     *
     * @param mixed $expected The expected value.
     * @param mixed $actual The actual value.
     * @param string $message Optional failure message.
     * @return void
     * @throws \Exception If not equal.
     */
    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->recordAssertion();
        if ($expected !== $actual) {
            $prefix = $message !== '' ? "$message: " : '';
            throw new \Exception($prefix . "Expected: " . print_r($expected, true) . ", Actual: " . print_r($actual, true));
        }
    }

    /**
     * Asserts that two variables are not strictly equal.
     *
     * @param mixed $expected The unexpected value.
     * @param mixed $actual The actual value.
     * @param string $message Optional failure message.
     * @return void
     * @throws \Exception If equal.
     */
    protected function assertNotEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->recordAssertion();
        if ($expected === $actual) {
            $prefix = $message !== '' ? "$message: " : '';
            throw new \Exception($prefix . "Expected value to not equal: " . print_r($actual, true));
        }
    }

    /**
     * Asserts that a variable is strictly true.
     *
     * @param mixed $actual The value to check.
     * @param string $message Optional failure message.
     * @return void
     * @throws \Exception If not true.
     */
    protected function assertTrue(mixed $actual, string $message = ''): void
    {
        $this->recordAssertion();
        if ($actual !== true) {
            $prefix = $message !== '' ? "$message: " : '';
            throw new \Exception($prefix . "Expected true, got " . print_r($actual, true));
        }
    }

    /**
     * Asserts that a variable is strictly false.
     *
     * @param mixed $actual The value to check.
     * @param string $message Optional failure message.
     * @return void
     * @throws \Exception If not false.
     */
    protected function assertFalse(mixed $actual, string $message = ''): void
    {
        $this->recordAssertion();
        if ($actual !== false) {
            $prefix = $message !== '' ? "$message: " : '';
            throw new \Exception($prefix . "Expected false, got " . print_r($actual, true));
        }
    }

    /**
     * Asserts that a variable is null.
     *
     * @param mixed $actual The value to check.
     * @param string $message Optional failure message.
     * @return void
     * @throws \Exception If not null.
     */
    protected function assertNull(mixed $actual, string $message = ''): void
    {
        $this->recordAssertion();
        if ($actual !== null) {
            $prefix = $message !== '' ? "$message: " : '';
            throw new \Exception($prefix . "Expected null, got " . print_r($actual, true));
        }
    }

    /**
     * Asserts that a variable is not null.
     *
     * @param mixed $actual The value to check.
     * @param string $message Optional failure message.
     * @return void
     * @throws \Exception If null.
     */
    protected function assertNotNull(mixed $actual, string $message = ''): void
    {
        $this->recordAssertion();
        if ($actual === null) {
            $prefix = $message !== '' ? "$message: " : '';
            throw new \Exception($prefix . "Expected non-null value, got null");
        }
    }

    /**
     * Asserts the element count of an array or Countable.
     *
     * @param int $expectedCount The expected number of items.
     * @param array<mixed>|\Countable $haystack The collection to count.
     * @param string $message Optional failure message.
     * @return void
     * @throws \Exception If count does not match.
     */
    protected function assertCount(int $expectedCount, array|\Countable $haystack, string $message = ''): void
    {
        $this->recordAssertion();
        $actualCount = count($haystack);
        if ($expectedCount !== $actualCount) {
            $prefix = $message !== '' ? "$message: " : '';
            throw new \Exception($prefix . "Expected count $expectedCount, got $actualCount");
        }
    }

    /**
     * Asserts that an array contains a given key.
     *
     * @param string|int $key The expected key.
     * @param array<mixed> $array The array to inspect.
     * @param string $message Optional failure message.
     * @return void
     * @throws \Exception If key does not exist.
     */
    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        $this->recordAssertion();
        if (!array_key_exists($key, $array)) {
            $prefix = $message !== '' ? "$message: " : '';
            throw new \Exception($prefix . "Failed asserting that array has key '$key'");
        }
    }

    /**
     * Asserts that a string contains a substring.
     *
     * @param string $needle The substring to search for.
     * @param string $haystack The string to search within.
     * @param string $message Optional failure message.
     * @return void
     * @throws \Exception If not contained.
     */
    protected function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        $this->recordAssertion();
        if (strpos($haystack, $needle) === false) {
            $prefix = $message !== '' ? "$message: " : '';
            throw new \Exception($prefix . "Expected string to contain '$needle', got '$haystack'");
        }
    }

    /**
     * Asserts that executing a callback throws a specific exception.
     *
     * @param callable $callback The callback to execute.
     * @param string $expectedExceptionClass The expected exception class name.
     * @param string|null $expectedMessage Optional exact or substring message to match.
     * @return \Throwable The caught exception.
     * @throws \Exception If exception is not thrown or does not match.
     */
    protected function assertThrows(callable $callback, string $expectedExceptionClass, ?string $expectedMessage = null): \Throwable
    {
        $this->recordAssertion();
        try {
            $callback();
        } catch (\Throwable $e) {
            if (!($e instanceof $expectedExceptionClass)) {
                throw new \Exception("Expected exception $expectedExceptionClass, got " . get_class($e) . ": " . $e->getMessage());
            }
            if ($expectedMessage !== null && $e->getMessage() !== $expectedMessage && strpos($e->getMessage(), $expectedMessage) === false) {
                throw new \Exception("Expected exception message '$expectedMessage', got '{$e->getMessage()}'");
            }
            return $e;
        }
        throw new \Exception("Expected exception $expectedExceptionClass was not thrown");
    }

    /**
     * Expects an exception of the given class to be thrown during the current test method.
     *
     * @param string $exceptionClass The expected exception class name.
     * @return void
     */
    protected function expectException(string $exceptionClass): void
    {
        $this->recordAssertion();
        $this->expectedExceptionClass = $exceptionClass;
    }

    /**
     * Expects the thrown exception message to contain the given string.
     *
     * @param string $message The expected message substring.
     * @return void
     */
    protected function expectExceptionMessage(string $message): void
    {
        $this->recordAssertion();
        $this->expectedExceptionMessage = $message;
    }

    /**
     * Asserts that executing a callback does not throw any exception.
     *
     * @param callable $callback The callback to execute.
     * @param string $message Optional failure message.
     * @return void
     * @throws \Exception If the callback throws any Throwable.
     */
    protected function assertDoesNotThrow(callable $callback, string $message = ''): void
    {
        $this->recordAssertion();
        try {
            $callback();
        } catch (\Throwable $e) {
            $prefix = $message !== '' ? "$message: " : '';
            throw new \Exception($prefix . "Expected no exception, but caught " . get_class($e) . ": " . $e->getMessage());
        }
    }

    /**
     * Unconditionally fails a test with the given message.
     *
     * @param string $message Failure description.
     * @return never
     * @throws \Exception Always thrown to fail the current test.
     */
    protected function fail(string $message = 'Test failed explicitly'): never
    {
        $this->recordAssertion();
        throw new \Exception($message);
    }

    /**
     * Recursively removes a temporary directory and its contents safely without shell exec.
     *
     * @param string $dir The directory path to remove.
     * @return void
     */
    protected function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}
