<?php

/**
 * Tests for the documentation coverage auditor (`bin/check_docs.php`).
 *
 * @package Cdd\Tests\Scripts
 */

declare(strict_types=1);

namespace Cdd\Tests\Scripts;

use Cdd\Tests\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/bin/check_docs.php';

/**
 * Verifies AST documentation auditing rules in `bin/check_docs.php`.
 */
class CheckDocsTest extends TestCase
{
    /**
     * Tests `audit_php_source_docs` and `has_uncaught_throw` across documented and undocumented PHP code constructs.
     *
     * @return void
     */
    public function testAuditPhpSourceDocs(): void
    {
        $validCode = <<<'PHP'
<?php
/**
 * Valid test file.
 *
 * @package Cdd\Tests
 */
declare(strict_types=1);

/**
 * Sample documented class.
 */
class DocumentedSample {
    /** @var string Constant A. */
    public const CONST_A = 'a', CONST_B = 'b';

    /** @var int Property X. */
    public int $x = 1, $y = 2;

    /**
     * Constructor.
     *
     * @param int $x Initial X.
     */
    public function __construct(int $x) {
        $this->x = $x;
    }

    /**
     * Method that throws inside catch and finally.
     *
     * @param string $msg Error message.
     * @return string Result string.
     * @throws \RuntimeException When an error occurs.
     */
    public function riskyMethod(string $msg): string {
        try {
            throw new \RuntimeException($msg);
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }
}
PHP;
        $resValid = \audit_php_source_docs($validCode, 'ValidSample.php');
        $this->assertEquals($resValid['total'], $resValid['documented']);
        $this->assertEmpty($resValid['details']);

        $invalidCode = <<<'PHP'
<?php
class UndocumentedClass {
    public const C1 = 1;
    public int $p1 = 1;
    public function badFunc(int $arg1) {
        try {
            $ok = 1;
        } finally {
            throw new \RuntimeException('uncaught in finally');
        }
    }
}
/**
 * Missing param, return, throws.
 */
function partialDocFunc(string $missingParam) {
    if ($missingParam === '') {
        throw new \RuntimeException('fail');
    }
}
PHP;
        $resInvalid = \audit_php_source_docs($invalidCode, 'InvalidSample.php');
        $this->assertNotEquals($resInvalid['total'], $resInvalid['documented']);
        $this->assertNotEmpty($resInvalid['details']);
    }
}
