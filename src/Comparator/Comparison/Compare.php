<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison;

use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Internal\Check;

final class Compare
{
    /**
     * @param ComparatorReady|callable|null $comparator
     */
    public static function values($comparator = null): Comparison
    {
        return Comparison::simple(Check::VALUE, $comparator);
    }
    
    /**
     * @param ComparatorReady|callable|null $comparator
     */
    public static function keys($comparator = null): Comparison
    {
        return Comparison::simple(Check::KEY, $comparator);
    }
    
    /**
     * @param ComparatorReady|callable|null $valueComparator
     * @param ComparatorReady|callable|null $keyComparator
     */
    public static function valuesAndKeysSeparately($valueComparator = null, $keyComparator = null): Comparison
    {
        return Comparison::double(Check::ANY, $valueComparator, $keyComparator);
    }
    
    /**
     * @param ComparatorReady|callable|null $valueComparator
     * @param ComparatorReady|callable|null $keyComparator
     */
    public static function bothValuesAndKeysTogether($valueComparator = null, $keyComparator = null): Comparison
    {
        return Comparison::double(Check::BOTH, $valueComparator, $keyComparator);
    }
    
    /**
     * By default it works like method bothValuesAndKeys(). In addition, this method allows to pass
     * single comparator used to compare both values and keys in full assoc mode instead of two independent comparators.
     *
     * @param ComparatorReady|callable|null $comparator
     */
    public static function assoc($comparator = null): Comparison
    {
        return Comparison::simple(Check::BOTH, $comparator);
    }
    
    /**
     * Compares full pairs (key,value).
     *
     * @param ComparatorReady|callable|null $valueComparator
     * @param ComparatorReady|callable|null $keyComparator
     */
    public static function pairs($valueComparator = null, $keyComparator = null): Comparison
    {
        return Comparison::pair($valueComparator, $keyComparator);
    }
}