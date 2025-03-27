<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Sorting;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Exception\ComparatorExceptionFactory;
use FiiSoft\Jackdaw\Internal\Check;

final class Key
{
    /**
     * @param ComparatorReady|callable|null $comparator
     */
    public static function asc($comparator = null): Sorting
    {
        return self::from(false, $comparator);
    }
    
    /**
     * @param ComparatorReady|callable|null $comparator
     */
    public static function desc($comparator = null): Sorting
    {
        return self::from(true, $comparator);
    }
    
    /**
     * @param ComparatorReady|callable|null $comparator
     */
    public static function from(bool $reversed, $comparator = null): Sorting
    {
        $comparator = Comparators::getAdapter($comparator);
        
        if ($comparator instanceof GenericComparator && $comparator->isFullAssoc()) {
            throw ComparatorExceptionFactory::invalidSortingCallable('key');
        }
        
        return Sorting::create($reversed, $comparator, Check::KEY);
    }
}