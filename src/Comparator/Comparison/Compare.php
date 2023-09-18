<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Internal\Check;

final class Compare
{
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function values($comparator = null): Comparison
    {
        return Comparison::simple(Check::VALUE, $comparator);
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function keys($comparator = null): Comparison
    {
        return Comparison::simple(Check::KEY, $comparator);
    }
    
    /**
     * @param Comparator|callable|null $valueComparator
     * @param Comparator|callable|null $keyComparator
     */
    public static function valuesAndKeysSeparately($valueComparator = null, $keyComparator = null): Comparison
    {
        return Comparison::double(Check::ANY, $valueComparator, $keyComparator);
    }
    
    /**
     * @param Comparator|callable|null $valueComparator
     * @param Comparator|callable|null $keyComparator
     */
    public static function bothValuesAndKeysTogether($valueComparator = null, $keyComparator = null): Comparison
    {
        return Comparison::double(Check::BOTH, $valueComparator, $keyComparator);
    }
    
    /**
     * By default it works like method bothValuesAndKeys(). In addition, this method allows to pass
     * single comparator used to compare both values and keys in full assoc mode instead of two independent comparators.
     *
     * @param Comparator|callable|null $comparator
     */
    public static function assoc($comparator = null): Comparison
    {
        return Comparison::simple(Check::BOTH, $comparator);
    }
}