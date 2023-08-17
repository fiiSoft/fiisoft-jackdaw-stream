<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Aggregate extends BaseOperation
{
    private array $keys;
    private int $size;
    private int $index = 0;
    
    private array $aggregated = [];
    
    public function __construct(array $keys)
    {
        if ($this->isParamKeysValid($keys)) {
            $this->keys = \array_flip($keys);
            $this->size = \count($this->keys);
        } else {
            throw new \InvalidArgumentException('Invalid param keys');
        }
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
    
        if (isset($this->keys[$item->key])) {
            if ($this->size === 1) {
                $item->value = [$item->key => $item->value];
                $item->key = $this->index++;
                
                $this->next->handle($signal);
            } else {
                $this->aggregated[$item->key] = $item->value;
    
                if (\count($this->aggregated) === $this->size) {
                    $signal->item->value = $this->aggregated;
                    $signal->item->key = $this->index++;
                    
                    $this->aggregated = [];
                    $this->next->handle($signal);
                }
            }
        }
    }
    
    private function isParamKeysValid(array $keys): bool
    {
        if (empty($keys)) {
            return false;
        }
    
        foreach ($keys as $key) {
            if (\is_string($key)) {
                if ($key === '') {
                    return false;
                }
            } elseif (!\is_int($key)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->aggregated = [];
            $this->keys = [];
            
            parent::destroy();
        }
    }
}