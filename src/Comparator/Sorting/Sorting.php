<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Sorting;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\ComparisonSpec;
use FiiSoft\Jackdaw\Comparator\Sorting\Specs\DoubleSorting;
use FiiSoft\Jackdaw\Comparator\Sorting\Specs\SingleSorting;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\ValueKeyComparator;
use FiiSoft\Jackdaw\Internal\Check;

abstract class Sorting implements ComparisonSpec
{
    /**
     * @param ComparatorReady|callable|null $sorting
     */
    final public static function prepare($sorting): self
    {
        return $sorting instanceof self ? $sorting : self::create(false, $sorting);
    }
    
    /**
     * @param ComparatorReady|callable|null $sorting
     */
    final public static function reverse($sorting = null, int $mode = Check::VALUE): self
    {
        return self::create(true, $sorting, $mode);
    }
    
    /**
     * @param ComparatorReady|callable|null $sorting
     */
    final public static function create(bool $reversed = false, $sorting = null, int $mode = Check::VALUE): self
    {
        if ($sorting instanceof self) {
            return $reversed ? $sorting->getReversed() : $sorting;
        }
        
        $comparator = Comparators::getAdapter($sorting);
        
        if ($comparator instanceof ValueKeyComparator
            || ($comparator instanceof GenericComparator && $comparator->isFullAssoc())
        ) {
            $mode = Check::BOTH;
        }
        
        return new SingleSorting($reversed, $comparator, $mode);
    }
    
    final public static function double(Sorting $first, Sorting $second): self
    {
        return new DoubleSorting($first, $second);
    }
    
    protected function __construct()
    {
    }
    
    abstract public function isReversed(): bool;
    
    abstract public function getReversed(): self;
}