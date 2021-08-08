<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Limitable;
use SplHeap;
use SplMaxHeap;
use SplMinHeap;

final class SortLimited extends BaseOperation implements Limitable
{
    /** @var Comparator|null  */
    private $comparator = null;
    
    /** @var bool */
    private $reversed;
    
    /** @var int */
    private $mode;
    
    /** @var int */
    private $limit;
    
    /** @var int */
    private $count = 0;
    
    /** @var SplHeap<Item> */
    private $items;
    
    /**
     * @param int $limit
     * @param Comparator|callable|null $comparator
     * @param int $mode
     * @param bool $reversed
     */
    public function __construct(
        int $limit,
        $comparator = null,
        int $mode = Check::VALUE,
        bool $reversed = false
    ) {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Invalid param limit');
        }
        
        $this->comparator = Comparators::getAdapter($comparator);
        $this->mode = Check::getMode($mode);
        $this->limit = $limit;
        $this->reversed = $reversed;
        
        $this->items = $this->createHeap();
    }
    
    public function handle(Signal $signal)
    {
        if ($this->count === $this->limit) {
            if ($this->items->compare($signal->item, $this->items->top()) < 0) {
                $this->items->extract();
                $this->items->insert($signal->item->copy());
            }
        } else {
            $this->items->insert($signal->item->copy());
            ++$this->count;
        }
    }
    
    public function streamingFinished(Signal $signal)
    {
        $signal->restartFrom($this->next, \array_reverse(\iterator_to_array($this->items, false)));
    }
    
    private function createHeap(): SplHeap
    {
        switch ($this->mode) {
            case Check::VALUE:
                if ($this->comparator === null) {
                    if ($this->reversed) {
                        return new class extends SplMinHeap {
                            /**
                             * @param Item $value1
                             * @param Item $value2
                             * @return int
                             */
                            public function compare($value1, $value2) {
                                return $value2->value <=> $value1->value;
                            }
                        };
                    }
    
                    return new class extends SplMaxHeap {
                        /**
                         * @param Item $value1
                         * @param Item $value2
                         * @return int
                         */
                        public function compare($value1, $value2) {
                            return $value1->value <=> $value2->value;
                        }
                    };
                }
    
                if ($this->reversed) {
                    return new class ($this->comparator) extends SplMinHeap {
                        /** @var Comparator */
                        private $comparator;
        
                        public function __construct(Comparator $comparator) {
                            $this->comparator = $comparator;
                        }
                        
                        /**
                         * @param Item $value1
                         * @param Item $value2
                         * @return int
                         */
                        public function compare($value1, $value2) {
                            return $this->comparator->compare($value2->value, $value1->value);
                        }
                    };
                }
    
                return new class ($this->comparator) extends SplMaxHeap {
                    /** @var Comparator */
                    private $comparator;
        
                    public function __construct(Comparator $comparator) {
                        $this->comparator = $comparator;
                    }
        
                    /**
                     * @param Item $value1
                     * @param Item $value2
                     * @return int
                     */
                    public function compare($value1, $value2) {
                        return $this->comparator->compare($value1->value, $value2->value);
                    }
                };
            
            case Check::KEY:
                if ($this->comparator === null) {
                    if ($this->reversed) {
                        return new class extends SplMinHeap {
                            /**
                             * @param Item $value1
                             * @param Item $value2
                             * @return int
                             */
                            public function compare($value1, $value2) {
                                return $value2->key <=> $value1->key;
                            }
                        };
                    }
    
                    return new class extends SplMaxHeap {
                        /**
                         * @param Item $value1
                         * @param Item $value2
                         * @return int
                         */
                        public function compare($value1, $value2) {
                            return $value1->key <=> $value2->key;
                        }
                    };
                }
    
                if ($this->reversed) {
                    return new class ($this->comparator) extends SplMinHeap {
                        /** @var Comparator */
                        private $comparator;
        
                        public function __construct(Comparator $comparator) {
                            $this->comparator = $comparator;
                        }
        
                        /**
                         * @param Item $value1
                         * @param Item $value2
                         * @return int
                         */
                        public function compare($value1, $value2) {
                            return $this->comparator->compare($value2->key, $value1->key);
                        }
                    };
                }
    
                return new class ($this->comparator) extends SplMaxHeap {
                    /** @var Comparator */
                    private $comparator;
        
                    public function __construct(Comparator $comparator) {
                        $this->comparator = $comparator;
                    }
        
                    /**
                     * @param Item $value1
                     * @param Item $value2
                     * @return int
                     */
                    public function compare($value1, $value2) {
                        return $this->comparator->compare($value1->key, $value2->key);
                    }
                };
    
            default:
                if ($this->comparator === null) {
                    if ($this->reversed) {
                        return new class extends SplMinHeap {
                            /**
                             * @param Item $value1
                             * @param Item $value2
                             * @return int
                             */
                            public function compare($value1, $value2) {
                                return $value2->value <=> $value1->value ?: $value2->key <=> $value1->key;
                            }
                        };
                    }
    
                    return new class extends SplMaxHeap {
                        /**
                         * @param Item $value1
                         * @param Item $value2
                         * @return int
                         */
                        public function compare($value1, $value2) {
                            return $value1->value <=> $value2->value ?: $value1->key <=> $value2->key;
                        }
                    };
                }
    
                if ($this->reversed) {
                    return new class ($this->comparator) extends SplMinHeap {
                        /** @var Comparator */
                        private $comparator;
        
                        public function __construct(Comparator $comparator) {
                            $this->comparator = $comparator;
                        }
        
                        /**
                         * @param Item $value1
                         * @param Item $value2
                         * @return int
                         */
                        public function compare($value1, $value2) {
                            return $this->comparator->compareAssoc(
                                $value2->value, $value1->value, $value2->key, $value1->key
                            );
                        }
                    };
                }
    
                return new class ($this->comparator) extends SplMaxHeap {
                    /** @var Comparator */
                    private $comparator;
        
                    public function __construct(Comparator $comparator) {
                        $this->comparator = $comparator;
                    }
        
                    /**
                     * @param Item $value1
                     * @param Item $value2
                     * @return int
                     */
                    public function compare($value1, $value2) {
                        return $this->comparator->compareAssoc(
                            $value1->value, $value2->value, $value1->key, $value2->key
                        );
                    }
                };
        }
    }
    
    public function reverseOrder()
    {
        $this->reversed = !$this->reversed;
        $this->items = $this->createHeap();
    }
    
    public function applyLimit(int $limit)
    {
        $this->limit = \min($this->limit, $limit);
    }
    
    public function limit(): int
    {
        return $this->limit;
    }
}