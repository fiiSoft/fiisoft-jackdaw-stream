<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Zip;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\Zip;
use FiiSoft\Jackdaw\Producer\Producer;

final class FullSizeZip extends Zip
{
    private int $size;
    
    /** @var Producer[] */
    private array $producers;
    
    /** @var Item[] */
    private array $items = [];
    
    /** @var \Iterator[] */
    private array $iterators = [];
    
    protected function __construct(Producer ...$producers)
    {
        parent::__construct();
        
        $this->producers = $producers;
        $this->size = \count($this->producers);
        
        for ($i = 0; $i < $this->size; ++$i) {
            $this->items[$i] = new Item();
            $this->iterators[$i] = Helper::createItemProducer($this->items[$i], $this->producers[$i]);
        }
    }
    
    public function handle(Signal $signal): void
    {
        $nextValue = [$signal->item->value];
        
        for ($i = 0; $i < $this->size; ++$i) {
            if ($this->iterators[$i]->valid()) {
                $nextValue[] = $this->items[$i]->value;
                $this->iterators[$i]->next();
            } else {
                $nextValue[] = null;
            }
        }
        
        $signal->item->value = $nextValue;
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $nextValue = [$value];
            
            for ($i = 0; $i < $this->size; ++$i) {
                if ($this->iterators[$i]->valid()) {
                    $nextValue[] = $this->items[$i]->value;
                    $this->iterators[$i]->next();
                } else {
                    $nextValue[] = null;
                }
            }
            
            yield $key => $nextValue;
        }
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            
            foreach ($this->producers as $producer) {
                $producer->destroy();
            }
            
            $this->producers = $this->items = $this->iterators = [];
            $this->size = 0;
            
            parent::destroy();
        }
    }
}