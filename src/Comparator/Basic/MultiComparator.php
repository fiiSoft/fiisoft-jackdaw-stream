<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Exception\InvalidParamException;

final class MultiComparator extends BaseComparator
{
    /** @var Comparator[] */
    private array $comparators = [];
    
    /**
     * @param ComparatorReady|callable $comparators
     */
    public function __construct(...$comparators)
    {
        $this->addComparators($comparators);
        
        if (empty($this->comparators)) {
            throw InvalidParamException::byName('comparators');
        }
    }
    
    /**
     * @inheritDoc
     */
    public function compare($value1, $value2): int
    {
        foreach ($this->comparators as $comparator) {
            $compare = $comparator->compare($value1, $value2);
            if ($compare !== 0) {
                return $compare;
            }
        }
        
        return 0;
    }
    
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        foreach ($this->comparators as $comparator) {
            $compare = $comparator->compareAssoc($value1, $value2, $key1, $key2);
            if ($compare !== 0) {
                return $compare;
            }
        }
        
        return 0;
    }
    
    /**
     * @param array<ComparatorReady|callable|null> $comparators
     */
    public function addComparators(array $comparators): void
    {
        foreach ($comparators as $comparator) {
            $comparator = Comparators::getAdapter($comparator);
            if ($comparator !== null) {
                $this->comparators[] = $comparator;
            }
        }
    }
}