<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Producer\Generator\Exception\GeneratorExceptionFactory;
use FiiSoft\Jackdaw\Producer\Tech\LimitedProducer;

final class RandomInt extends LimitedProducer
{
    private int $min;
    private int $max;
    
    public function __construct(int $min = 1, int $max = \PHP_INT_MAX, int $limit = \PHP_INT_MAX)
    {
        parent::__construct($limit);
        
        if ($max <= $min) {
            throw GeneratorExceptionFactory::maxCannotBeLessThanOrEqualToMin();
        }
    
        $this->min = $min;
        $this->max = $max;
    }
    
    public function getIterator(): \Generator
    {
        $count = -1;
        $limit = $this->limit - 1;
        
        while ($count !== $limit) {
            yield ++$count => \random_int($this->min, $this->max);
        }
    }
}