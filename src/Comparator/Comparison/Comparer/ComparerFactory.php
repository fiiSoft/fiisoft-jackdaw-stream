<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison\Comparer;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparer;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Internal\Check;

final class ComparerFactory
{
    public static function createComparer(Comparison $comparison): Comparer
    {
        $comparators = $comparison->getComparators();
        
        if (\count($comparators) === 1) {
            return self::singleComparer($comparison->mode(), ...$comparators);
        }
        
        if (\count($comparators) === 2) {
            return self::doubleComparer($comparison->mode(), ...$comparators);
        }
        
        throw new \UnexpectedValueException('Unexpected number of comparators returned from Comparison');
    }
    
    /**
     * @param Comparable|callable|null $comparator
     */
    private static function singleComparer(int $mode, $comparator): Comparer
    {
        switch ($mode) {
            case Check::VALUE:
                return new Comparer\Single\ValueComparer($comparator);
            case Check::KEY:
                return new Comparer\Single\KeyComparer($comparator);
            default:
                return new Comparer\Single\AssocComparer($comparator);
        }
    }
    
    /**
     * @param Comparable|callable|null $first
     * @param Comparable|callable|null $second
     */
    private static function doubleComparer(int $mode, $first, $second): Comparer
    {
        switch ($mode) {
            case Check::BOTH:
                return new Comparer\Double\FullComparer($first, $second);
            case Check::ANY:
                return new Comparer\Double\SeparatedComparer($first, $second);
            default:
                throw new \UnexpectedValueException('Unexpected value of mode ('.$mode.')');
        }
    }
}