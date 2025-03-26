<?php declare(strict_types=1);

use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\LogicalAnd\LogicalToBooleanRector;
use Rector\CodingStyle\Rector\Assign\SplitDoubleAssignRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassConst\SplitGroupedClassConstantsRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\Property\SplitGroupedPropertiesRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector;
use Rector\DeadCode\Rector\If_\UnwrapFutureCompatibleIfPhpVersionRector;
use Rector\DeadCode\Rector\PropertyProperty\RemoveNullPropertyInitializationRector;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php74\Rector\Ternary\ParenthesizeNestedTernaryRector;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\Strict\Rector\Ternary\DisallowedShortTernaryRuleFixerRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeBasedOnPHPUnitDataProviderRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByParentCallTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictStringParamConcatRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $configurator): void {
    
    $configurator->import(SetList::CODE_QUALITY);
    $configurator->import(SetList::CODING_STYLE);
    $configurator->import(SetList::DEAD_CODE);
    $configurator->import(SetList::STRICT_BOOLEANS);
    $configurator->import(SetList::GMAGICK_TO_IMAGICK);
    $configurator->import(SetList::NAMING);
    $configurator->import(SetList::PHP_74);
    $configurator->import(SetList::PRIVATIZATION);
    $configurator->import(SetList::TYPE_DECLARATION);
    $configurator->import(SetList::INSTANCEOF);
    
    $configurator->skip([
        RemoveUselessReturnTagRector::class,
        NewlineAfterStatementRector::class,
        TypedPropertyFromAssignsRector::class,
        RenameParamToMatchTypeRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        RenameForeachValueVariableToMatchExprVariableRector::class,
        RemoveUselessParamTagRector::class,
        DisallowedEmptyRuleFixerRector::class,
        RenameVariableToMatchNewTypeRector::class,
        RenameVariableToMatchMethodCallReturnTypeRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        AddReturnTypeDeclarationBasedOnParentClassMethodRector::class,
        RenamePropertyToMatchTypeRector::class,
        AddMethodCallBasedStrictParamTypeRector::class,
        CombineIfRector::class,
        RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class,
        RemoveNullPropertyInitializationRector::class,
        RemoveUnusedForeachKeyRector::class,
        MakeInheritedMethodVisibilitySameAsParentRector::class,
        CatchExceptionNameMatchingTypeRector::class,
        NewlineBeforeNewAssignSetRector::class,
        SplitDoubleAssignRector::class,
        SplitGroupedPropertiesRector::class,
        TypedPropertyFromStrictConstructorRector::class,
        DisallowedShortTernaryRuleFixerRector::class,
        ParamTypeByParentCallTypeRector::class,
        SplitGroupedClassConstantsRector::class,
        UnusedForeachValueToArrayKeysRector::class,
        InlineIfToExplicitIfRector::class,
        AddParamTypeBasedOnPHPUnitDataProviderRector::class,
        StringClassNameToClassConstantRector::class,
        UnwrapFutureCompatibleIfPhpVersionRector::class,
        ParenthesizeNestedTernaryRector::class,
        StrictStringParamConcatRector::class,
        LogicalToBooleanRector::class,
        SimplifyIfElseToTernaryRector::class,
        RemoveUnreachableStatementRector::class,
    ]);
    
    $configurator->skip([
        __DIR__ . '/src/Filter/OnlyIn/Mixed/MixedBothOnlyIn.php',
        __DIR__ . '/src/Comparator/Comparison/Comparer/ComparerFactory.php',
        __DIR__ . '/src/Producer/Generator/CombinedGeneral.php',
    ]);
};