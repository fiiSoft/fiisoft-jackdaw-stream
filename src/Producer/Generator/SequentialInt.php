<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\LimitedProducer;

final class SequentialInt extends LimitedProducer
{
    private int $start;
    private int $step;
    
    public function __construct(int $start = 1, int $step = 1, int $limit = \PHP_INT_MAX)
    {
        parent::__construct($limit);
        
        if ($step === 0) {
            throw new \InvalidArgumentException('Param step cannot be 0');
        }
        
        $this->start = $start;
        $this->step = $step;
    }
    
    public function feed(Item $item): \Generator
    {
        $count = 0;
        $current = $this->start;
        
        while ($count !== $this->limit) {
            
            $item->key = $count++;
            $item->value = $current;
            
            yield;
            
            $current += $this->step;
        }
    }
    
    public function getLast(): ?Item
    {
        if ($this->count() === 0) {
            return null;
        }
        
        $last = $this->start + $this->step * ($this->limit - 1);
        
        return new Item($this->limit - 1, $last);
    }
}