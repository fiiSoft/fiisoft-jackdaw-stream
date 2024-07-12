<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Aggregate;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\Aggregate;

final class FullAggregate extends Aggregate
{
    /** @var array<string|int, mixed> */
    private array $aggregated = [];
    
    private int $size;
    
    /**
     * @param array<string|int> $keys
     */
    protected function __construct(array $keys)
    {
        parent::__construct($keys);
        
        $this->size = \count($keys);
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if (isset($this->keys[$item->key])) {
            $this->aggregated[$item->key] = $item->value;
            
            if (\count($this->aggregated) === $this->size) {
                $signal->item->value = $this->aggregated;
                $signal->item->key = $this->index++;
                $this->aggregated = [];
                
                $this->next->handle($signal);
            }
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (isset($this->keys[$key])) {
                $this->aggregated[$key] = $value;
                
                if (\count($this->aggregated) === $this->size) {
                    yield $this->index++ => $this->aggregated;
                    
                    $this->aggregated = [];
                }
            }
        }
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->aggregated = [];
            
            parent::destroy();
        }
    }
}