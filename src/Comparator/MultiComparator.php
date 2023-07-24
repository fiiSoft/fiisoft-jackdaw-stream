<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator;

final class MultiComparator implements Comparator
{
    /** @var Comparator[] */
    private array $comparators = [];
    
    /**
     * @param Comparator|callable $comparators
     */
    public function __construct(...$comparators)
    {
        if (empty($comparators)) {
            throw new \InvalidArgumentException('Invalid param comparators');
        }
        
        $this->addComparators($comparators);
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
    
    public function addComparators(array $comparators): void
    {
        foreach ($comparators as $comparator) {
            if ($comparator === null) {
                $comparator = Comparators::default();
            } else {
                $comparator = Comparators::getAdapter($comparator);
            }
            
            $this->comparators[] = $comparator;
        }
    }
}