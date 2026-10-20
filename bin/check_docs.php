<?php

/**
 * Documentation coverage verification script using nikic/php-parser.
 *
 * Verifies 100% documentation coverage across `src/`, `bin/`, `scripts/`, `tests/`,
 * `router.php`, and `run_test_server.php`:
 * - File-level docblocks with `@package`
 * - Class, interface, trait, and enum docblocks
 * - Property and class constant docblocks with `@var`
 * - Function and method docblocks, including `@param` for every parameter,
 *   `@return` for every function/method, and `@throws` when uncaught exceptions are thrown.
 *
 * @package Cdd\Bin
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

/**
 * Checks whether a statement array contains any uncaught `throw` expression outside of `try` blocks.
 *
 * @param array<int, Node> $nodes AST nodes to inspect.
 * @return bool True if an uncaught throw expression is present.
 */
function has_uncaught_throw(array $nodes): bool
{
    foreach ($nodes as $node) {
        if ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction || $node instanceof Node\Stmt\ClassLike) {
            continue;
        }
        if ($node instanceof Node\Expr\Throw_) {
            return true;
        }
        if ($node instanceof Node\Stmt\TryCatch) {
            foreach ($node->catches as $catch) {
                if (has_uncaught_throw($catch->stmts)) {
                    return true;
                }
            }
            if ($node->finally !== null && has_uncaught_throw($node->finally->stmts)) {
                return true;
            }
            continue;
        }
        foreach ($node->getSubNodeNames() as $subName) {
            $sub = $node->$subName;
            if ($sub instanceof Node) {
                if (has_uncaught_throw([$sub])) {
                    return true;
                }
            } elseif (is_array($sub)) {
                $childNodes = array_filter($sub, static fn ($item): bool => $item instanceof Node);
                if (has_uncaught_throw($childNodes)) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * Audits a PHP source code string for 100% documentation coverage.
 *
 * @param string $code The PHP source code to audit.
 * @param string $relPath Logical or relative file path for error reporting.
 * @return array{total: int, documented: int, details: array<int, string>} Audit summary.
 */
function audit_php_source_docs(string $code, string $relPath): array
{
    $parser = (new ParserFactory())->createForNewestSupportedVersion();
    $nodeFinder = new NodeFinder();

    $totalItems = 0;
    $documentedItems = 0;
    $undocumentedDetails = [];

    // 1. Check file-level docblock
    $totalItems++;
    $tokens = token_get_all($code);
    $fileDocText = null;
    foreach ($tokens as $token) {
        if (is_array($token)) {
            if ($token[0] === T_DOC_COMMENT) {
                $fileDocText = $token[1];
                break;
            }
            if (in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION], true)) {
                break;
            }
        }
    }
    if ($fileDocText !== null && strpos($fileDocText, '@package') !== false) {
        $documentedItems++;
    } else {
        $undocumentedDetails[] = "$relPath -> Missing file-level docblock (with @package)";
    }

    $stmts = $parser->parse($code) ?? [];

    // 2. Check classes, interfaces, traits, enums
    $classLikes = $nodeFinder->findInstanceOf($stmts, Node\Stmt\ClassLike::class);
    foreach ($classLikes as $classLike) {
        if ($classLike->name === null) {
            continue;
        }
        $className = $classLike->name->toString();
        $totalItems++;
        if ($classLike->getDocComment() !== null) {
            $documentedItems++;
        } else {
            $undocumentedDetails[] = "$relPath -> Class/Interface $className missing docblock";
        }

        // 3. Check all properties in multi-property declarations and verify @var
        foreach ($classLike->getProperties() as $prop) {
            $propDoc = $prop->getDocComment();
            foreach ($prop->props as $p) {
                $propName = $p->name->toString();
                $totalItems++;
                if ($propDoc !== null && str_contains($propDoc->getText(), '@var')) {
                    $documentedItems++;
                } else {
                    $undocumentedDetails[] = "$relPath -> Property {$className}::\${$propName} missing docblock with @var";
                }
            }
        }

        // 4. Check all class constants in multi-constant declarations and verify @var
        foreach ($classLike->getConstants() as $constStmt) {
            $constDoc = $constStmt->getDocComment();
            foreach ($constStmt->consts as $c) {
                $constName = $c->name->toString();
                $totalItems++;
                if ($constDoc !== null && str_contains($constDoc->getText(), '@var')) {
                    $documentedItems++;
                } else {
                    $undocumentedDetails[] = "$relPath -> Constant {$className}::{$constName} missing docblock with @var";
                }
            }
        }
    }

    // 5. Check standalone functions and class methods
    /** @var array<int, Node\Stmt\Function_|Node\Stmt\ClassMethod> $funcLikes */
    $funcLikes = array_merge(
        $nodeFinder->findInstanceOf($stmts, Node\Stmt\Function_::class),
        $nodeFinder->findInstanceOf($stmts, Node\Stmt\ClassMethod::class)
    );

    foreach ($funcLikes as $func) {
        $funcName = $func->name->toString();
        $totalItems++;
        $docComment = $func->getDocComment();
        if ($docComment === null) {
            $undocumentedDetails[] = "$relPath -> Function/Method {$funcName}() missing docblock";
            continue;
        }

        $docText = $docComment->getText();
        $funcValid = true;

        // Verify @param tags for all parameters
        foreach ($func->getParams() as $param) {
            if ($param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
                $paramName = $param->var->name;
                if (!preg_match('/@param\s+.+?\s+(?:\.\.\.)?&?\$' . preg_quote($paramName, '/') . '\b/', $docText)) {
                    $funcValid = false;
                    $undocumentedDetails[] = "$relPath -> {$funcName}() missing @param for \${$paramName}";
                }
            }
        }

        // Verify @return tag (except __construct)
        if ($funcName !== '__construct') {
            if (!preg_match('/@return\s+[^\s]+/', $docText)) {
                $funcValid = false;
                $undocumentedDetails[] = "$relPath -> {$funcName}() missing @return tag";
            }
        }

        // Verify @throws tag if function body contains uncaught Throw_
        $stmtsInFunc = $func->getStmts() ?? [];
        if (has_uncaught_throw($stmtsInFunc) && !preg_match('/@throws\s+[^\s]+/', $docText)) {
            $funcValid = false;
            $undocumentedDetails[] = "$relPath -> {$funcName}() throws exception but is missing @throws tag";
        }

        if ($funcValid) {
            $documentedItems++;
        }
    }

    return [
        'total' => $totalItems,
        'documented' => $documentedItems,
        'details' => $undocumentedDetails,
    ];
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $rootDir = dirname(__DIR__);
    $targetDirs = [
        $rootDir . '/src',
        $rootDir . '/bin',
        $rootDir . '/scripts',
        $rootDir . '/tests',
    ];
    $targetFiles = [
        $rootDir . '/router.php',
        $rootDir . '/run_test_server.php',
        $rootDir . '/bin/cdd-php',
    ];

    $allFiles = [];
    foreach ($targetDirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $allFiles[$file->getPathname()] = true;
            }
        }
    }
    foreach ($targetFiles as $tf) {
        if (is_file($tf)) {
            $allFiles[$tf] = true;
        }
    }
    ksort($allFiles);

    $totalItems = 0;
    $documentedItems = 0;
    $undocumentedDetails = [];

    foreach (array_keys($allFiles) as $filePath) {
        $relPath = substr($filePath, strlen($rootDir) + 1);
        $code = (string) file_get_contents($filePath);
        $res = audit_php_source_docs($code, $relPath);
        $totalItems += $res['total'];
        $documentedItems += $res['documented'];
        $undocumentedDetails = array_merge($undocumentedDetails, $res['details']);
    }

    $percent = $totalItems > 0 ? round(($documentedItems / $totalItems) * 100, 2) : 100;
    foreach ($undocumentedDetails as $detail) {
        echo "Undocumented: $detail
";
    }
    echo "Doc Coverage: $percent%
";

    if ($documentedItems !== $totalItems) {
        exit(1);
    }
    exit(0);
}
