<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\LimitedProducer;

final class RandomInt extends LimitedProducer
{
    private int $min;
    private int $max;
    
    public function __construct(int $min = 1, int $max = \PHP_INT_MAX, int $limit = \PHP_INT_MAX)
    {
        parent::__construct($limit);
        
        if ($max <= $min) {
            throw new \InvalidArgumentException('Max cannot be less than or equal to min');
        }
    
        $this->min = $min;
        $this->max = $max;
    }
    
    public function feed(Item $item): \Generator
    {
        $count = 0;
        
        while ($count !== $this->limit) {
            
            $item->key = $count++;
            $item->value = \random_int($this->min, $this->max);
            
            yield;
        }
    }
    
    public function getLast(): ?Item
    {
        return null;
    }
}