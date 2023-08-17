<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\SimpleFinalOperation;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Stream;

final class HasOnly extends SimpleFinalOperation
{
    private array $values;
    
    private bool $hasOnly = true;
    private int $mode;
    
    public function __construct(Stream $stream, array $values, int $mode = Check::VALUE)
    {
        $this->values = \array_unique($values, \SORT_REGULAR);
        $this->mode = Check::getMode($mode);
        
        parent::__construct($stream);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->mode === Check::VALUE) {
            $this->testSingle($signal->item->value, $signal);
        } elseif ($this->mode === Check::KEY) {
            $this->testSingle($signal->item->key, $signal);
        } elseif ($this->mode === Check::ANY) {
            $this->testValueOrKey($signal->item, $signal);
        } else {
            $this->testValueAndKey($signal->item, $signal);
        }
    }
    
    /**
     * @param mixed $search
     */
    private function testSingle($search, Signal $signal): bool
    {
        if (!\in_array($search, $this->values, true)) {
            $this->testFailed($signal);
            return true;
        }
        
        return false;
    }
    
    private function testValueAndKey(Item $item, Signal $signal): bool
    {
        if (!\in_array($item->value, $this->values, true)
            || !\in_array($item->key, $this->values, true)
        ) {
            $this->testFailed($signal);
            return true;
        }
        
        return false;
    }
    
    private function testValueOrKey(Item $item, Signal $signal): bool
    {
        if (!\in_array($item->value, $this->values, true)
            && !\in_array($item->key, $this->values, true)
        ) {
            $this->testFailed($signal);
            return true;
        }
        
        return false;
    }
    
    private function testFailed(Signal $signal): void
    {
        $this->hasOnly = false;
        $signal->stop();
    }
    
    public function hasResult(): bool
    {
        return true;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->hasOnly);
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
                if ($this->testValueOrKey($item, $signal)) {
                    break;
                }
            } elseif ($this->testValueAndKey($item, $signal)) {
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
                if ($this->testValueOrKey($item, $signal)) {
                    break;
                }
            } elseif ($this->testValueAndKey($item, $signal)) {
                break;
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
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
                if ($this->testValueOrKey($item, $signal)) {
                    break;
                }
            } elseif ($this->testValueAndKey($item, $signal)) {
                break;
            }
        }
        
        if (isset($item)) {
            $signal->item->key = $item->key;
            $signal->item->value = $item->value;
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->values = [];
            
            parent::destroy();
        }
    }
}