<?php

/**
 * Documentation coverage verification script using nikic/php-parser.
 *
 * Verifies 100% documentation coverage across `src/`, `bin/`, `scripts/`, `tests/`,
 * `router.php`, and `run_test_server.php`:
 * - File-level docblocks with `@package`
 * - Class, interface, trait, and enum docblocks
 * - Property and class constant docblocks
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

$parser = (new ParserFactory())->createForNewestSupportedVersion();
$nodeFinder = new NodeFinder();

$totalItems = 0;
$documentedItems = 0;
$undocumentedDetails = [];

foreach (array_keys($allFiles) as $filePath) {
    $relPath = substr($filePath, strlen($rootDir) + 1);
    $code = (string) file_get_contents($filePath);

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

        // 3. Check properties
        foreach ($classLike->getProperties() as $prop) {
            $propName = $prop->props[0]->name->toString();
            $totalItems++;
            if ($prop->getDocComment() !== null) {
                $documentedItems++;
            } else {
                $undocumentedDetails[] = "$relPath -> Property {$className}::\${$propName} missing docblock";
            }
        }

        // 4. Check class constants
        foreach ($classLike->getConstants() as $constStmt) {
            $constName = $constStmt->consts[0]->name->toString();
            $totalItems++;
            if ($constStmt->getDocComment() !== null) {
                $documentedItems++;
            } else {
                $undocumentedDetails[] = "$relPath -> Constant {$className}::{$constName} missing docblock";
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
                if (!preg_match('/@param\s+.+?\s+&?\$' . preg_quote($paramName, '/') . '\b/', $docText)) {
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
}

$percent = $totalItems > 0 ? round(($documentedItems / $totalItems) * 100, 2) : 100;
foreach ($undocumentedDetails as $detail) {
    echo "Undocumented: $detail\n";
}
echo "Doc Coverage: $percent%\n";

if ($documentedItems !== $totalItems) {
    exit(1);
}
exit(0);
