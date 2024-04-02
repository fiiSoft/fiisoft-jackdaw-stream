<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison\Comparer;

use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparer;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;
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
        
        //@codeCoverageIgnoreStart
        throw ImpossibleSituationException::create('Unexpected number of comparators returned from Comparison');
        //@codeCoverageIgnoreEnd
    }
    
    /**
     * @param ComparatorReady|callable|null $comparator
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
     * @param ComparatorReady|callable|null $valueComparator
     * @param ComparatorReady|callable|null $keyComparator
     */
    private static function doubleComparer(int $mode, $valueComparator, $keyComparator): Comparer
    {
        switch ($mode) {
            case Check::BOTH:
                return new Comparer\Double\FullComparer($valueComparator, $keyComparator);
            case Check::ANY:
                return new Comparer\Double\SeparatedComparer($valueComparator, $keyComparator);
            //@codeCoverageIgnoreStart
            default:
                throw ImpossibleSituationException::create('Unexpected value of mode ('.$mode.')');
            //@codeCoverageIgnoreEnd
        }
    }
}