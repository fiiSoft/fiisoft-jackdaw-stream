<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\SortLimited;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;

final class HeapFactory
{
    /**
     * @return \SplHeap<Item>
     */
    public static function createHeapForSorting(Sorting $sorting): \SplHeap
    {
        $reversed = $sorting->isReversed();
        $comparator = $sorting->comparator();
        $mode = $sorting->mode();
        
        if ($mode === Check::VALUE) {
            if ($comparator !== null) {
                return $reversed ? self::value_custom_reversed($comparator) : self::value_custom_normal($comparator);
            }
            
            return $reversed ? self::value_standard_reversed() : self::value_standard_normal();
        }
        
        if ($mode === Check::KEY) {
            if ($comparator !== null) {
                return $reversed ? self::key_custom_reversed($comparator) : self::key_custom_normal($comparator);
            }
            
            return $reversed ? self::key_standard_reversed() : self::key_standard_normal();
        }
        
        if ($comparator !== null) {
            return $reversed ? self::assoc_custom_reversed($comparator) : self::assoc_custom_normal($comparator);
        }
        
        return $reversed ? self::assoc_standard_reversed() : self::assoc_standard_normal();
    }
    
    /**
     * @return \SplHeap<Item>
     */
    private static function value_custom_normal(Comparator $comparator): \SplHeap
    {
        return new class ($comparator) extends \SplHeap {
            private Comparator $comparator;
            
            public function __construct(Comparator $comparator) {
                $this->comparator = $comparator;
            }
            
            /**
             * @param Item $value1
             * @param Item $value2
             */
            public function compare($value1, $value2): int {
                return $this->comparator->compare($value1->value, $value2->value);
            }
        };
    }
    
    /**
     * @return \SplHeap<Item>
     */
    private static function value_custom_reversed(Comparator $comparator): \SplHeap
    {
        return new class ($comparator) extends \SplHeap {
            private Comparator $comparator;
            
            public function __construct(Comparator $comparator) {
                $this->comparator = $comparator;
            }
            
            /**
             * @param Item $value1
             * @param Item $value2
             */
            public function compare($value1, $value2): int {
                return $this->comparator->compare($value2->value, $value1->value);
            }
        };
    }
    
    /**
     * @return \SplHeap<Item>
     */
    private static function key_custom_normal(Comparator $comparator): \SplHeap
    {
        return new class ($comparator) extends \SplHeap {
            private Comparator $comparator;
            
            public function __construct(Comparator $comparator) {
                $this->comparator = $comparator;
            }
            
            /**
             * @param Item $value1
             * @param Item $value2
             */
            public function compare($value1, $value2): int {
                return $this->comparator->compare($value1->key, $value2->key);
            }
        };
    }
    
    /**
     * @return \SplHeap<Item>
     */
    private static function key_custom_reversed(Comparator $comparator): \SplHeap
    {
        return new class ($comparator) extends \SplHeap {
            private Comparator $comparator;
            
            public function __construct(Comparator $comparator) {
                $this->comparator = $comparator;
            }
            
            /**
             * @param Item $value1
             * @param Item $value2
             */
            public function compare($value1, $value2): int {
                return $this->comparator->compare($value2->key, $value1->key);
            }
        };
    }
    
    /**
     * @return \SplHeap<Item>
     */
    private static function assoc_custom_normal(Comparator $comparator): \SplHeap
    {
        return new class ($comparator) extends \SplHeap {
            private Comparator $comparator;
            
            public function __construct(Comparator $comparator) {
                $this->comparator = $comparator;
            }
            
            /**
             * @param Item $value1
             * @param Item $value2
             */
            public function compare($value1, $value2): int {
                return $this->comparator->compareAssoc(
                    $value1->value, $value2->value, $value1->key, $value2->key
                );
            }
        };
    }
    
    /**
     * @return \SplHeap<Item>
     */
    private static function assoc_custom_reversed(Comparator $comparator): \SplHeap
    {
        return new class ($comparator) extends \SplHeap {
            private Comparator $comparator;
            
            public function __construct(Comparator $comparator) {
                $this->comparator = $comparator;
            }
            
            /**
             * @param Item $value1
             * @param Item $value2
             */
            public function compare($value1, $value2): int {
                return $this->comparator->compareAssoc(
                    $value2->value, $value1->value, $value2->key, $value1->key
                );
            }
        };
    }
    
    /**
     * @return \SplHeap<Item>
     */
    private static function assoc_standard_normal(): \SplHeap
    {
        return new class extends \SplHeap {
            /**
             * @param Item $value1
             * @param Item $value2
             */
            public function compare($value1, $value2): int
            {
                return \gettype($value1->value) <=> \gettype($value2->value)
                    ?: $value1->value <=> $value2->value
                    ?: \gettype($value1->key) <=> \gettype($value2->key)
                    ?: $value1->key <=> $value2->key;
            }
        };
    }
    
    /**
     * @return \SplHeap<Item>
     */
    private static function assoc_standard_reversed(): \SplHeap
    {
        return new class extends \SplHeap {
            /**
             * @param Item $value1
             * @param Item $value2
             */
            public function compare($value1, $value2): int
            {
                return \gettype($value2->value) <=> \gettype($value1->value)
                    ?: $value2->value <=> $value1->value
                    ?: \gettype($value2->key) <=> \gettype($value1->key)
                    ?: $value2->key <=> $value1->key;
            }
        };
    }
    
    /**
     * @return \SplHeap<Item>
     */
    private static function value_standard_normal(): \SplHeap
    {
        return new class extends \SplHeap {
            /**
             * @param Item $value1
             * @param Item $value2
             */
            public function compare($value1, $value2): int {
                return \gettype($value1->value) <=> \gettype($value2->value) ?: $value1->value <=> $value2->value;
            }
        };
    }
    
    /**
     * @return \SplHeap<Item>
     */
    private static function value_standard_reversed(): \SplHeap
    {
        return new class extends \SplHeap {
            /**
             * @param Item $value1
             * @param Item $value2
             */
            public function compare($value1, $value2): int {
                return \gettype($value2->value) <=> \gettype($value1->value) ?: $value2->value <=> $value1->value;
            }
        };
    }
    
    /**
     * @return \SplHeap<Item>
     */
    private static function key_standard_normal(): \SplHeap
    {
        return new class extends \SplHeap {
            /**
             * @param Item $value1
             * @param Item $value2
             */
            public function compare($value1, $value2): int {
                return \gettype($value1->key) <=> \gettype($value2->key) ?: $value1->key <=> $value2->key;
            }
        };
    }
    
    /**
     * @return \SplHeap<Item>
     */
    private static function key_standard_reversed(): \SplHeap
    {
        return new class extends \SplHeap {
            /**
             * @param Item $value1
             * @param Item $value2
             */
            public function compare($value1, $value2): int {
                return \gettype($value2->key) <=> \gettype($value1->key) ?: $value2->key <=> $value1->key;
            }
        };
    }
}