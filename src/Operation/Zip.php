<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\Producer\Producers;

final class Zip extends BaseOperation
{
    private int $size;
    private bool $prepared = false;
    
    /** @var Producer[] */
    private array $producers = [];
    
    /** @var Item[] */
    private array $items = [];
    
    /** @var \Generator[] */
    private array $generators = [];
    
    /** @var array<ProducerReady|resource|callable|iterable|scalar> */
    private array $sources;
    
    /**
     * @param array<ProducerReady|resource|callable|iterable|scalar> $sources
     */
    public function __construct(array $sources)
    {
        $this->sources = $sources;
    }
    
    public function handle(Signal $signal): void
    {
        if (!$this->prepared) {
            $this->prepare();
        }
        
        if ($this->size === 0) {
            $signal->item->value = [$signal->item->value];
        } else {
            $nextValue = [$signal->item->value];
            
            for ($i = 0; $i < $this->size; ++$i) {
                if ($this->generators[$i]->valid()) {
                    $nextValue[] = $this->items[$i]->value;
                    $this->generators[$i]->next();
                } else {
                    $nextValue[] = null;
                }
            }
            
            $signal->item->value = $nextValue;
        }
        
        $this->next->handle($signal);
    }
    
    private function prepare(): void
    {
        foreach (Producers::prepare($this->sources) as $source) {
            $this->producers[] = Producers::getAdapter($source);
        }
        
        $this->size = \count($this->producers);
        
        for ($i = 0; $i < $this->size; ++$i) {
            $this->items[$i] = new Item();
            $this->generators[$i] = $this->producers[$i]->feed($this->items[$i]);
        }
        
        $this->prepared = true;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            
            foreach ($this->producers as $producer) {
                $producer->destroy();
            }
            
            $this->producers = $this->items = $this->generators = $this->sources = [];
            
            parent::destroy();
        }
    }
}