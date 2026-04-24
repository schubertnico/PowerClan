<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/admin',
        __DIR__ . '/config.inc.php',
        __DIR__ . '/mysql.inc.php',
        __DIR__ . '/functions.inc.php',
        __DIR__ . '/header.inc.php',
        __DIR__ . '/footer.inc.php',
        __DIR__ . '/index.php',
        __DIR__ . '/member.php',
        __DIR__ . '/wars.php',
        __DIR__ . '/showpic.php',
        __DIR__ . '/install.php',
    ])
    ->withSkip([
        __DIR__ . '/vendor',
        __DIR__ . '/.docker',
        __DIR__ . '/logs',
    ])
    ->withPhpSets(php84: true)
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    );
