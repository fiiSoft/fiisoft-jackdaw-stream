<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Accumulate;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\Accumulate;

final class AccumulateReindexKeys extends Accumulate
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if ($this->reverse XOR $this->filter->isAllowed($item->value, $item->key)) {
            $this->data[] = $item->value;
        } elseif (!empty($this->data)) {
            $item->key = $this->index++;
            $item->value = $this->data;
            $this->data = [];
            
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->reverse XOR $this->filter->isAllowed($value, $key)) {
                $this->data[] = $value;
            } elseif (!empty($this->data)) {
                yield $this->index++ => $this->data;
                
                $this->data = [];
            }
        }
        
        if (!empty($this->data)) {
            yield $this->index++ => $this->data;
            
            $this->data = [];
        }
    }
}