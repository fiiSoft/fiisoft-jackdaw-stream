<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Comparison\Specs\DoubleComparison;
use FiiSoft\Jackdaw\Comparator\Comparison\Specs\SingleComparison;
use FiiSoft\Jackdaw\Comparator\ComparisonSpec;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\ValueKeyComparator;
use FiiSoft\Jackdaw\Internal\Check;

abstract class Comparison implements ComparisonSpec
{
    /**
     * @param Comparable|callable|null $comparator
     */
    public static function create(int $mode, $comparator = null): self
    {
        $comparator = Comparators::getAdapter($comparator);
        $mode = self::adjustMode($mode, $comparator);
        
        return self::simple($mode, $comparator);
    }
    
    /**
     * @param Comparison|Comparable|callable|null $comparison
     */
    public static function prepare($comparison): self
    {
        if ($comparison instanceof self) {
            return $comparison;
        }
        
        $comparison = Comparators::getAdapter($comparison);
        $mode = self::adjustMode(Check::VALUE, $comparison);
        
        return self::simple($mode, $comparison);
    }
    
    private static function adjustMode(int $mode, ?Comparator $comparator): int
    {
        return $comparator instanceof GenericComparator && $comparator->isFullAssoc() ? Check::BOTH : $mode;
    }
    
    /**
     * @param Comparable|callable|null $comparator
     */
    public static function simple(int $mode = Check::VALUE, $comparator = null): self
    {
        $comparator = Comparators::getAdapter($comparator);
        
        if ($comparator instanceof ValueKeyComparator) {
            $mode = Check::BOTH;
        } elseif ($comparator instanceof GenericComparator
            && $comparator->isFullAssoc()
            && ($mode === Check::VALUE || $mode === Check::KEY)
        ) {
            throw self::wrongCallableException($mode);
        }
        
        return new SingleComparison($comparator, $mode);
    }
    
    private static function wrongCallableException(int $mode): \Throwable
    {
        return new \LogicException(
            'Cannot compare by '.($mode === Check::VALUE ? 'values' : 'keys')
            .' with callable that requires four arguments'
        );
    }
    
    /**
     * @param Comparable|callable|null $valueComparator
     * @param Comparable|callable|null $keyComparator
     */
    public static function double(int $mode = Check::VALUE, $valueComparator = null, $keyComparator = null): self
    {
        return new DoubleComparison($mode, $valueComparator, $keyComparator);
    }
    
    /**
     * @return array<Comparable|callable|null> only one or two comparators
     */
    abstract public function getComparators(): array;
}