<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\BaseProducer;

final class RandomInt extends BaseProducer
{
    private int $min;
    private int $max;
    
    private int $count = 0;
    private int $limit;
    
    public function __construct(int $min = 1, int $max = \PHP_INT_MAX, int $limit = \PHP_INT_MAX)
    {
        if ($max <= $min) {
            throw new \InvalidArgumentException('Max cannot be less than or equal to min');
        }
    
        if ($limit < 0) {
            throw new \InvalidArgumentException('Invalid param limit');
        }
        
        $this->min = $min;
        $this->max = $max;
        $this->limit = $limit;
    }
    
    public function feed(Item $item): \Generator
    {
        while ($this->count !== $this->limit) {
            
            $item->key = $this->count++;
            $item->value = \random_int($this->min, $this->max);
            
            yield;
        }
    }
}