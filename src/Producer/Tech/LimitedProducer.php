<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Tech;

abstract class LimitedProducer extends CountableProducer
{
    protected int $limit;
    
    public function __construct(int $limit = \PHP_INT_MAX)
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException('Invalid param limit');
        }
        
        $this->limit = $limit;
    }
    
    final public function count(): int
    {
        return $this->limit;
    }
}