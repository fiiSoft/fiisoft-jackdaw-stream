<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\DataCollector;
use FiiSoft\Jackdaw\Operation\Internal\Limitable;
use FiiSoft\Jackdaw\Operation\State\SortLimited\BufferNotFull;
use FiiSoft\Jackdaw\Operation\State\SortLimited\SingleItem;
use FiiSoft\Jackdaw\Operation\State\SortLimited\State;
use FiiSoft\Jackdaw\Producer\Internal\ReverseItemsIterator;
use FiiSoft\Jackdaw\Producer\Producer;

final class SortLimited extends BaseOperation implements Limitable, DataCollector
{
    private ?Comparator $comparator = null;
    private State $state;
    
    private bool $reversed;
    private int $mode;
    private int $limit;
    
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
        
        $this->prepareToWork();
    }
    
    public function handle(Signal $signal): void
    {
        $this->state->hold($signal->item);
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($this->state->isEmpty()) {
            return parent::streamingFinished($signal);
        }
        
        $producer = new ReverseItemsIterator($this->state->getCollectedItems());
        
        if ($this->next instanceof DataCollector) {
            $signal->continueFrom($this->next);
            
            return $this->next->collectDataFromProducer($producer, $signal, false);
        }
        
        $signal->restartWith($producer, $this->next);
        
        return true;
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($producer->feed($item) as $_) {
            $this->state->hold($item);
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($data as $item->key => $item->value) {
            $this->state->hold($item);
        }
        
        return $this->streamingFinished($signal);
    }
    
    /**
     * @param Item[] $items
     */
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        foreach ($items as $item) {
            $this->state->hold($item);
        }
        
        if (isset($item)) {
            $signal->item->key = $item->key;
            $signal->item->value = $item->value;
        }
        
        return $this->streamingFinished($signal);
    }
    
    private function prepareToWork(): void
    {
        if ($this->limit === 1) {
            $this->state = new SingleItem($this, $this->mode, $this->reversed, $this->comparator);
        } else {
            $this->state = new BufferNotFull($this, $this->createHeap(), $this->limit);
        }
    }
    
    private function createHeap(): \SplHeap
    {
        switch ($this->mode) {
            case Check::VALUE:
                if ($this->comparator === null) {
                    if ($this->reversed) {
                        return new class extends \SplHeap {
                            /**
                             * @param Item $value1
                             * @param Item $value2
                             */
                            public function compare($value1, $value2): int {
                                return $value2->value <=> $value1->value;
                            }
                        };
                    }
                    
                    return new class extends \SplHeap {
                        /**
                         * @param Item $value1
                         * @param Item $value2
                         */
                        public function compare($value1, $value2): int {
                            return $value1->value <=> $value2->value;
                        }
                    };
                }
                
                if ($this->reversed) {
                    return new class ($this->comparator) extends \SplHeap {
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
                
                return new class ($this->comparator) extends \SplHeap {
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
            
            case Check::KEY:
                if ($this->comparator === null) {
                    if ($this->reversed) {
                        return new class extends \SplHeap {
                            /**
                             * @param Item $value1
                             * @param Item $value2
                             */
                            public function compare($value1, $value2): int {
                                return $value2->key <=> $value1->key;
                            }
                        };
                    }
                    
                    return new class extends \SplHeap {
                        /**
                         * @param Item $value1
                         * @param Item $value2
                         */
                        public function compare($value1, $value2): int {
                            return $value1->key <=> $value2->key;
                        }
                    };
                }
                
                if ($this->reversed) {
                    return new class ($this->comparator) extends \SplHeap {
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
                
                return new class ($this->comparator) extends \SplHeap {
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
            
            default:
                if ($this->comparator === null) {
                    if ($this->reversed) {
                        return new class extends \SplHeap {
                            /**
                             * @param Item $value1
                             * @param Item $value2
                             */
                            public function compare($value1, $value2): int {
                                return $value2->value <=> $value1->value ?: $value2->key <=> $value1->key;
                            }
                        };
                    }
                    
                    return new class extends \SplHeap {
                        /**
                         * @param Item $value1
                         * @param Item $value2
                         */
                        public function compare($value1, $value2): int {
                            return $value1->value <=> $value2->value ?: $value1->key <=> $value2->key;
                        }
                    };
                }
                
                if ($this->reversed) {
                    return new class ($this->comparator) extends \SplHeap {
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
                
                return new class ($this->comparator) extends \SplHeap {
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
    }
    
    public function applyLimit(int $limit): bool
    {
        $limit = \min($this->limit, $limit);
        
        if ($limit !== $this->limit) {
            $this->limit = $limit;
            $this->state->setLength($this->limit);
            
            if ($this->limit === 1) {
                $this->prepareToWork();
            }
        }
        
        return true;
    }
    
    public function limit(): int
    {
        return $this->limit;
    }
    
    public function transitTo(State $state): void
    {
        $this->state = $state;
    }
    
    protected function __clone()
    {
        parent::__clone();
        
        $this->prepareToWork();
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->state->destroy();
            
            parent::destroy();
        }
    }
}