<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\DataAware;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;

final class First extends SimpleFinal implements DataAware
{
    private ?Item $item = null;
    
    public function handle(Signal $signal): void
    {
        $this->item = clone $signal->item;
        
        $signal->stop();
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->item = new Item($key, $value);
            break;
        }
        
        yield;
    }
    
    public function getResult(): ?Item
    {
        return $this->item;
    }
    
    public function isReindexed(): bool
    {
        return false;
    }
    
    protected function __clone()
    {
        parent::__clone();
        
        $this->item = null;
    }
    
    /**
     * @inheritDoc
     */
    public function setCollectedItems(array $items): void
    {
        if (empty($items)) {
            return;
        }
        
        $key = \array_key_first($items);
        
        $this->item = $items[$key];
    }
    
    /**
     * @inheritDoc
     */
    public function setCollectedData(array $data): void
    {
        if (empty($data)) {
            return;
        }
        
        $key = \array_key_first($data);
        
        $this->item = new Item($key, $data[$key]);
    }
}