<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\AccumulateSeparate;

use FiiSoft\Jackdaw\Internal\Signal;

final class SeparateKeepKeys extends Separate
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if ($this->filter->isAllowed($item->value, $item->key)) {
            if (!empty($this->data)) {
                $item->key = ++$this->index;
                $item->value = $this->data;
                $this->data = [];
                
                $this->next->handle($signal);
            }
            
            return;
        }
        
        $this->data[$item->key] = $item->value;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed($value, $key)) {
                if (!empty($this->data)) {
                    yield ++$this->index => $this->data;
                    
                    $this->data = [];
                }
                
                continue;
            }
            
            $this->data[$key] = $value;
        }
        
        if (!empty($this->data)) {
            yield ++$this->index => $this->data;
            
            $this->data = [];
        }
    }
}