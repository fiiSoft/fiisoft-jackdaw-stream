<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\DispatchOperation;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\HandlerReady;

final class Unzip extends DispatchOperation
{
    private Item $item;
    
    private int $count;
    
    /**
     * @param HandlerReady[] $consumers
     */
    public function __construct(array $consumers)
    {
        parent::__construct($consumers);
        
        $this->handlers = \array_values($this->handlers);
        $this->count = \count($this->handlers);
        
        $this->item = new Item();
    }
    
    public function handle(Signal $signal): void
    {
        $index = 0;
        
        $oryginal = $signal->item;
        $signal->item = $this->item;
        
        try {
            foreach ($oryginal->value as $signal->item->key => $signal->item->value) {
                $this->handlers[$index]->handle($signal);
                
                if (++$index === $this->count) {
                    break;
                }
            }
        } finally {
            $signal->item = $oryginal;
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            
            $index = 0;
            foreach ($value as $k => $v) {
                $this->handlers[$index]->handlePair($v, $k);
                
                if (++$index === $this->count) {
                    break;
                }
            }
            
            yield $key => $value;
        }
    }
}