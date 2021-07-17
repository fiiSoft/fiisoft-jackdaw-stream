<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Tail extends BaseOperation
{
    private \SplFixedArray $buffer;
    
    private int $numOfItems;
    private int $index = 0;
    
    public function __construct(int $numOfItems)
    {
        if ($numOfItems < 0) {
            throw new \InvalidArgumentException('Invalid param numOfItems');
        }
    
        $this->numOfItems = $numOfItems;
        $this->buffer = new \SplFixedArray($numOfItems);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->numOfItems > 0) {
            $this->buffer[$this->index] = $signal->item->copy();
            
            if (++$this->index === $this->numOfItems) {
                $this->index = 0;
            }
        }
    }
    
    public function streamingFinished(Signal $signal): void
    {
        $items = [];
        
        if ($this->buffer->count() > 0) {
            $count = \min($this->numOfItems, $this->buffer->count());
            
            for ($i = 0; $i < $count; ++$i) {
                if ($this->index === $count) {
                    $this->index = 0;
                }
                
                $items[] = $this->buffer[$this->index++];
            }
            
            $this->buffer->setSize(0);
        }
        
        $signal->restartFrom($this->next, $items);
    }
}