<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Adapter;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;
use FiiSoft\Jackdaw\Producer\Producer;

final class ProducerAdapter extends StateMapper
{
    private Item $item;
    private \Iterator $source;
    
    /**
     * @param Producer<string|int, mixed> $producer
     */
    public function __construct(Producer $producer)
    {
        $this->item = new Item();
        
        $this->source = Helper::createItemProducer($this->item, $producer);
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        if ($this->source->valid()) {
            $value = $this->item->value;
            $this->source->next();
            
            return $value;
        }
        
        return $this->isValueMapper ? $value : $key;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->source->valid()) {
                $value = $this->item->value;
                $this->source->next();
            }
            
            yield $key => $value;
        }
    }
    
    protected function buildKeyMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->source->valid()) {
                $key = $this->item->value;
                $this->source->next();
                
            }
            
            yield $key => $value;
        }
    }
}