<?php

use Rector\Arguments\Rector\ClassMethod\ArgumentAdderRector;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\ReplaceTestAnnotationWithPrefixedFunctionRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

//@see https://github.com/rectorphp/rector/blob/master/docs/how_to_ignore_rule_or_paths.md
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->noDiffs();
    $rectorConfig->cacheDirectory(__DIR__.'/tmp/rector');
    //$rectorConfig->autoloadPaths(['vendor/autoload.php']);
    // auto import fully qualified class names? [default: false]
    $rectorConfig->importNames(true, false);
    // skip root namespace classes, like \DateTime or \Exception [default: true]
    $rectorConfig->importShortClasses(false);
    $rectorConfig->removeUnusedImports(true);
    $rectorConfig->memoryLimit('-1');
    $rectorConfig->indent(' ', 4);
    // paths to refactor; solid alternative to CLI arguments
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests/src',
    ]);
    $rectorConfig->parallel(720);
    // here we can define, what sets of rules will be applied
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::EARLY_RETURN,
        SetList::DEAD_CODE,
        SetList::INSTANCEOF,
        SetList::TYPE_DECLARATION,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
    //@see https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#compacttovariablesrector
    //exclude some rectors or files
    $rectorConfig->skip([
        //single rule
        ArgumentAdderRector::class,
        CompactToVariablesRector::class,
        ReplaceTestAnnotationWithPrefixedFunctionRector::class,
        IssetOnPropertyObjectToPropertyExistsRector::class,
    ]);
};
