<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Comparison\Specs\DoubleComparison;
use FiiSoft\Jackdaw\Comparator\Comparison\Specs\SingleComparison;
use FiiSoft\Jackdaw\Comparator\ComparisonSpec;
use FiiSoft\Jackdaw\Comparator\Exception\ComparatorExceptionFactory;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\ValueKeyComparator;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class Comparison implements ComparisonSpec
{
    protected int $mode;
    
    private bool $isPairComp;
    
    /**
     * @param ComparatorReady|callable|null $comparator
     */
    final public static function create(int $mode, $comparator = null): self
    {
        $comparator = Comparators::getAdapter($comparator);
        $mode = self::adjustMode($mode, $comparator);
        
        return self::simple($mode, $comparator);
    }
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    final public static function prepare($comparison): self
    {
        if ($comparison instanceof self) {
            return $comparison;
        }
        
        $mode = $comparison instanceof ComparisonSpec ? $comparison->mode() : Check::VALUE;
        
        $comparison = Comparators::getAdapter($comparison);
        $mode = self::adjustMode($mode, $comparison);
        
        return self::simple($mode, $comparison);
    }
    
    private static function adjustMode(int $mode, ?Comparator $comparator): int
    {
        return $comparator instanceof GenericComparator && $comparator->isFullAssoc() ? Check::BOTH : $mode;
    }
    
    /**
     * @param ComparatorReady|callable|null $comparator
     */
    final public static function simple(int $mode = Check::VALUE, $comparator = null): self
    {
        $comparator = Comparators::getAdapter($comparator);
        
        if ($comparator instanceof ValueKeyComparator) {
            $mode = Check::BOTH;
        } elseif ($comparator instanceof GenericComparator
            && ($mode === Check::VALUE || $mode === Check::KEY)
            && $comparator->isFullAssoc()
        ) {
            throw ComparatorExceptionFactory::wrongComparisonCallable($mode);
        }
        
        return new SingleComparison($comparator, $mode);
    }
    
    /**
     * @param ComparatorReady|callable|null $valueComparator
     * @param ComparatorReady|callable|null $keyComparator
     */
    final public static function double(int $mode = Check::VALUE, $valueComparator = null, $keyComparator = null): self
    {
        return new DoubleComparison($mode, $valueComparator, $keyComparator);
    }
    
    /**
     * Compares full pairs (key,value).
     *
     * @param ComparatorReady|callable|null $valueComparator
     * @param ComparatorReady|callable|null $keyComparator
     */
    final public static function pair($valueComparator = null, $keyComparator = null): self
    {
        return new DoubleComparison(Check::BOTH, $valueComparator, $keyComparator, true);
    }
    
    protected function __construct(int $mode, bool $isPairComp = false)
    {
        $this->mode = Mode::get($mode);
        $this->isPairComp = $isPairComp;
    }
    
    final public function mode(): int
    {
        return $this->mode;
    }
    
    final public function isPairComparison(): bool
    {
        return $this->isPairComp;
    }
    
    /**
     * @return array<ComparatorReady|callable|null> only one or two comparators
     */
    abstract public function getComparators(): array;
}