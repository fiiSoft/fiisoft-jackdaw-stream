<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Adapter;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;
use FiiSoft\Jackdaw\Producer\Producer;

final class ProducerAdadpter extends BaseMapper
{
    private Item $item;
    private \Generator $source;
    
    public function __construct(Producer $producer)
    {
        $this->item = new Item();
        $this->source = $producer->feed($this->item);
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if ($this->source->valid()) {
            $value = $this->item->value;
            $this->source->next();
            
            return $value;
        }
        
        return $this->isValueMapper ? $value : $key;
    }
}