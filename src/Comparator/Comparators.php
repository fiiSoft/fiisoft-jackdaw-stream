<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator;

final class Comparators
{
    /**
     * @param Comparator|callable|null $comparator
     * @return Comparator|null
     */
    public static function getAdapter($comparator): ?Comparator
    {
        if ($comparator instanceof Comparator) {
            $adapter = $comparator;
        } elseif (\is_callable($comparator)) {
            $adapter = self::generic($comparator);
        } elseif ($comparator === null) {
            $adapter = null;
        } else {
            throw new \InvalidArgumentException('Invalid param comparator');
        }
        
        return $adapter;
    }
    
    public static function default(): DefaultComparator
    {
        return new DefaultComparator();
    }
    
    public static function generic(callable $comparator): GenericComparator
    {
        return new GenericComparator($comparator);
    }
    
    /**
     * @param string[]|int[] $fields
     * @return SortBy
     */
    public static function sortBy(array $fields): SortBy
    {
        return new SortBy($fields);
    }
}