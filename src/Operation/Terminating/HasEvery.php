<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\SimpleFinalOperation;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Stream;

final class HasEvery extends SimpleFinalOperation
{
    private array $values;
    private array $keys = [];
    
    private bool $hasEvery = false;
    private int $mode;
    
    public function __construct(Stream $stream, array $values, int $mode = Check::VALUE)
    {
        $this->values = \array_unique($values, \SORT_REGULAR);
        $this->mode = Check::getMode($mode);
        
        if ($this->mode === Check::BOTH) {
            $this->keys = $this->values;
        }
        
        parent::__construct($stream);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->mode === Check::VALUE) {
            $this->testSingle($signal->item->value, $signal);
        } elseif ($this->mode === Check::KEY) {
            $this->testSingle($signal->item->key, $signal);
        } elseif ($this->mode === Check::ANY) {
            $this->testSingle($signal->item->value, $signal) || $this->testSingle($signal->item->key, $signal);
        } else {
            $this->testValueAndKey($signal->item->value, $signal->item->key, $signal);
        }
    }
    
    private function testSingle($search, Signal $signal): bool
    {
        $pos = \array_search($search, $this->values, true);
        if ($pos !== false) {
            unset($this->values[$pos]);
            if (empty($this->values)) {
                $this->hasEvery = true;
                $signal->stop();
                return true;
            }
        }
        
        return false;
    }
    
    private function testValueAndKey($value, $key, Signal $signal): bool
    {
        if (!empty($this->values)) {
            $valPos = \array_search($value, $this->values, true);
            if ($valPos !== false) {
                unset($this->values[$valPos]);
            }
        }
        
        if (!empty($this->keys)) {
            $keyPos = \array_search($key, $this->keys, true);
            if ($keyPos !== false) {
                unset($this->keys[$keyPos]);
            }
        }
        
        if (empty($this->values) && empty($this->keys)) {
            $this->hasEvery = true;
            $signal->stop();
            return true;
        }
        
        return false;
    }
    
    public function hasResult(): bool
    {
        return true;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->hasEvery);
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($producer->feed($item) as $_) {
            if ($this->mode === Check::VALUE) {
                if ($this->testSingle($item->value, $signal)) {
                    break;
                }
            } elseif ($this->mode === Check::KEY) {
                if ($this->testSingle($item->key, $signal)) {
                    break;
                }
            } elseif ($this->mode === Check::ANY) {
                if ($this->testSingle($item->value, $signal) || $this->testSingle($item->key, $signal)) {
                    break;
                }
            } elseif ($this->testValueAndKey($item->value, $item->key, $signal)) {
                break;
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($data as $item->key => $item->value) {
            if ($this->mode === Check::VALUE) {
                if ($this->testSingle($item->value, $signal)) {
                    break;
                }
            } elseif ($this->mode === Check::KEY) {
                if ($this->testSingle($item->key, $signal)) {
                    break;
                }
            } elseif ($this->mode === Check::ANY) {
                if ($this->testSingle($item->value, $signal) || $this->testSingle($item->key, $signal)) {
                    break;
                }
            } elseif ($this->testValueAndKey($item->value, $item->key, $signal)) {
                break;
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    /**
     * @param bool $reindexed
     * @param Item[] $items
     */
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        foreach ($items as $item) {
            if ($this->mode === Check::VALUE) {
                if ($this->testSingle($item->value, $signal)) {
                    break;
                }
            } elseif ($this->mode === Check::KEY) {
                if ($this->testSingle($item->key, $signal)) {
                    break;
                }
            } elseif ($this->mode === Check::ANY) {
                if ($this->testSingle($item->value, $signal) || $this->testSingle($item->key, $signal)) {
                    break;
                }
            } elseif ($this->testValueAndKey($item->value, $item->key, $signal)) {
                break;
            }
        }
        
        if (isset($item)) {
            $signal->item->key = $item->key;
            $signal->item->value = $item->value;
        }
        
        return $this->streamingFinished($signal);
    }
}