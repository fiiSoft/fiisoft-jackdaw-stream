<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Sorting;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\ComparisonSpec;
use FiiSoft\Jackdaw\Comparator\Sorting\Specs\DoubleSorting;
use FiiSoft\Jackdaw\Comparator\Sorting\Specs\SingleSorting;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\ValueKeyComparator;
use FiiSoft\Jackdaw\Internal\Check;

abstract class Sorting implements ComparisonSpec
{
    /**
     * @param Sorting|Comparable|callable|null $sorting
     */
    public static function prepare($sorting): self
    {
        return $sorting instanceof self ? $sorting : self::create(false, $sorting);
    }
    
    /**
     * @param Sorting|Comparable|callable|null $sorting
     */
    public static function reverse($sorting = null, int $mode = Check::VALUE): self
    {
        return self::create(true, $sorting, $mode);
    }
    
    /**
     * @param Sorting|Comparable|callable|null $sorting
     */
    public static function create(bool $reversed = false, $sorting = null, int $mode = Check::VALUE): self
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
    
    public static function double(Sorting $first, Sorting $second): self
    {
        return new DoubleSorting($first, $second);
    }
    
    abstract public function isReversed(): bool;
    
    abstract public function getReversed(): self;
}