<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$scanRoots = ['app', 'routes', 'database', 'tests'];
$phpFiles = [];

foreach ($scanRoots as $scanRoot) {
    $directory = $root . DIRECTORY_SEPARATOR . $scanRoot;

    if (! is_dir($directory)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $phpFiles[] = $file->getPathname();
    }
}

sort($phpFiles);

$violations = [];

foreach ($phpFiles as $file) {
    $code = file_get_contents($file);

    if ($code === false) {
        fwrite(STDERR, "Unable to read {$file}\n");
        exit(1);
    }

    $tokens = token_get_all($code);
    $namespace = null;
    $imports = [];
    $declaredNames = [];
    $hasNamespace = false;
    $inUseStatement = false;
    $braceDepth = 0;

    $collectQualifiedName = static function (array $tokens, int &$index): string {
        $name = '';
        $count = count($tokens);

        for ($i = $index + 1; $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NS_SEPARATOR], true)) {
                $name .= $token[1];
                $index = $i;
                continue;
            }

            if (is_array($token) && $token[0] === T_WHITESPACE) {
                if ($name !== '') {
                    $index = $i;
                }
                continue;
            }

            break;
        }

        return trim($name, '\\');
    };

    $previousMeaningfulToken = static function (array $tokens, int $index): array|string|null {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $token;
        }

        return null;
    };

    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];

        if ($token === '{') {
            $braceDepth++;
            continue;
        }

        if ($token === '}') {
            $braceDepth = max(0, $braceDepth - 1);
            continue;
        }

        if (! is_array($token)) {
            continue;
        }

        [$id, $text, $line] = $token;

        if ($id === T_NAMESPACE) {
            $namespace = $collectQualifiedName($tokens, $i);
            $hasNamespace = $namespace !== '';
            continue;
        }

        if ($id === T_USE && $braceDepth === 0) {
            $statement = '';

            for ($j = $i + 1; $j < $count; $j++) {
                $next = $tokens[$j];

                if ($next === ';') {
                    $i = $j;
                    break;
                }

                $statement .= is_array($next) ? $next[1] : $next;
            }

            $statement = trim($statement);
            if ($statement === '' || str_contains($statement, 'function ') || str_contains($statement, 'const ')) {
                continue;
            }

            foreach (explode(',', $statement) as $importChunk) {
                $importChunk = trim($importChunk);
                if ($importChunk === '') {
                    continue;
                }

                if (preg_match('/^(.+?)\s+as\s+([A-Za-z_][A-Za-z0-9_]*)$/i', $importChunk, $matches) === 1) {
                    $fqcn = trim($matches[1], '\\ ');
                    $alias = $matches[2];
                } else {
                    $fqcn = trim($importChunk, '\\ ');
                    $parts = explode('\\', $fqcn);
                    $alias = end($parts);
                }

                $imports[$alias] = $fqcn;
            }

            continue;
        }

        if (in_array($id, [T_CLASS, T_TRAIT, T_ENUM], true)) {
            $name = $collectQualifiedName($tokens, $i);
            if ($name !== '') {
                $parts = explode('\\', $name);
                $declaredNames[] = end($parts);
            }
            continue;
        }

        if (! $hasNamespace || $id !== T_STRING || ! preg_match('/^[A-Z]/', $text)) {
            continue;
        }

        $nextIndex = $i + 1;
        while ($nextIndex < $count && is_array($tokens[$nextIndex]) && $tokens[$nextIndex][0] === T_WHITESPACE) {
            $nextIndex++;
        }

        if (($tokens[$nextIndex] ?? null) !== T_DOUBLE_COLON) {
            continue;
        }

        $className = $text;
        if (in_array(strtolower($className), ['self', 'static', 'parent'], true)) {
            continue;
        }

        if (isset($imports[$className]) || in_array($className, $declaredNames, true)) {
            continue;
        }

        $previous = $previousMeaningfulToken($tokens, $i);
        if (is_array($previous) && in_array($previous[0], [T_NEW, T_INSTANCEOF, T_EXTENDS, T_IMPLEMENTS, T_USE], true)) {
            continue;
        }

        if ($previous === '\\' || (is_array($previous) && $previous[0] === T_NS_SEPARATOR)) {
            continue;
        }

        $violations[] = [
            'file' => str_replace($root . DIRECTORY_SEPARATOR, '', $file),
            'line' => $line,
            'class' => $className,
            'usage' => $className . '::',
        ];
    }
}

if ($violations === []) {
    fwrite(STDOUT, "Missing static import check passed.\n");
    exit(0);
}

fwrite(STDERR, "Missing imports for static calls were found:\n");

foreach ($violations as $violation) {
    fwrite(
        STDERR,
        sprintf(
            "- %s:%d uses %s without import or local declaration\n",
            $violation['file'],
            $violation['line'],
            $violation['usage']
        )
    );
}

exit(1);
