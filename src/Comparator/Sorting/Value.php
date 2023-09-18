<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Sorting;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Internal\Check;

final class Value
{
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function asc($comparator = null): Sorting
    {
        return self::from(false, $comparator);
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function desc($comparator = null): Sorting
    {
        return self::from(true, $comparator);
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function from(bool $reversed, $comparator = null): Sorting
    {
        $comparator = Comparators::getAdapter($comparator);
        
        if ($comparator instanceof GenericComparator && $comparator->isFullAssoc()) {
            throw new \LogicException('Cannot sort by value with callable that requires four arguments');
        }
        
        return Sorting::create($reversed, $comparator, Check::VALUE);
    }
}