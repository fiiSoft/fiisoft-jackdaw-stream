<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\LastOperation;
use FiiSoft\Jackdaw\Operation\Internal\StreamPipeOperation;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Stream;

final class Unzip extends StreamPipeOperation
{
    private Item $item;
    
    private int $count;
    
    /**
     * @param array<Stream|LastOperation|ResultApi|Collector|Consumer|Reducer> $consumers
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
        if (\is_iterable($signal->item->value)) {
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
        } else {
            throw new \RuntimeException('Operation Unzip requires iterable values');
        }
        
        $this->next->handle($signal);
    }
}