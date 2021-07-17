<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;

final class SequentialInt implements Producer
{
    /** @var int */
    private $start;
    
    /** @var int */
    private $step;
    
    /** @var int */
    private $count = 0;
    
    /** @var int */
    private $limit;
    
    public function __construct(int $start = 1, int $step = 1, int $limit = \PHP_INT_MAX)
    {
        if ($step === 0) {
            throw new \InvalidArgumentException('Param step cannot be 0');
        }
        
        if ($limit < 0) {
            throw new \InvalidArgumentException('Invalid param limit');
        }
        
        $this->start = $start;
        $this->step = $step;
        $this->limit = $limit;
    }
    
    public function feed(Item $item): \Generator
    {
        $current = $this->start;
        
        while ($this->count !== $this->limit) {
            
            $item->key = $this->count++;
            $item->value = $current;
            
            yield;
            
            $current += $this->step;
        }
    }
}