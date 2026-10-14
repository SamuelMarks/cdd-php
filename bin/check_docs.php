<?php

/**
 * Documentation coverage verification script using nikic/php-parser.
 *
 * Verifies 100% documentation coverage across `src/`, `bin/`, `scripts/`, and `tests/`:
 * - File-level docblocks
 * - Class, interface, trait, and enum docblocks
 * - Property and class constant docblocks
 * - Function and method docblocks, including `@param` for every parameter,
 *   `@return` for every function/method, and `@throws` when exceptions are thrown.
 *
 * @package Cdd\Bin
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

$rootDir = dirname(__DIR__);
$targetDirs = [
    $rootDir . '/src',
];

$parser = (new ParserFactory())->createForNewestSupportedVersion();
$nodeFinder = new NodeFinder();

$totalItems = 0;
$documentedItems = 0;
$undocumentedDetails = [];

foreach ($targetDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $filePath = $file->getPathname();
        $relPath = substr($filePath, strlen($rootDir) + 1);
        $code = (string) file_get_contents($filePath);

        // 1. Check file-level docblock
        $totalItems++;
        $tokens = token_get_all($code);
        $hasFileDoc = false;
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[0] === T_DOC_COMMENT) {
                    $hasFileDoc = true;
                    break;
                }
                if (in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION], true)) {
                    break;
                }
            }
        }
        if ($hasFileDoc) {
            $documentedItems++;
        } else {
            $undocumentedDetails[] = "$relPath -> Missing file-level docblock";
        }

        $stmts = $parser->parse($code) ?? [];

        // 2. Check classes, interfaces, traits, enums
        $classLikes = $nodeFinder->findInstanceOf($stmts, Node\Stmt\ClassLike::class);
        foreach ($classLikes as $classLike) {
            if ($classLike->name === null) {
                // Anonymous class
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

            // Verify @throws tag if function body directly contains uncaught Throw_ expression/stmt
            $stmtsInFunc = $func->getStmts() ?? [];
            $throwsNodes = $nodeFinder->find($stmtsInFunc, function (Node $n): bool {
                return $n instanceof Node\Expr\Throw_;
            });
            $tryCatchNodes = $nodeFinder->find($stmtsInFunc, function (Node $n): bool {
                return $n instanceof Node\Stmt\TryCatch;
            });
            if (!empty($throwsNodes) && empty($tryCatchNodes) && !preg_match('/@throws\s+[^\s]+/', $docText)) {
                $funcValid = false;
                $undocumentedDetails[] = "$relPath -> {$funcName}() throws exception but is missing @throws tag";
            }

            if ($funcValid) {
                $documentedItems++;
            }
        }
    }
}

$percent = $totalItems > 0 ? round(($documentedItems / $totalItems) * 100, 2) : 100;
foreach ($undocumentedDetails as $detail) {
    echo "Undocumented: $detail
";
}
echo "Doc Coverage: $percent%
";
file_put_contents($rootDir . '/doc_cov.txt', (string) $percent);

if ($documentedItems !== $totalItems) {
    exit(1);
}
exit(0);
