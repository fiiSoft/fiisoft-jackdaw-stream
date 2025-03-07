<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Producer\Tech\LimitedProducer;

final class SequentialInt extends LimitedProducer
{
    private int $start;
    private int $step;
    
    public function __construct(int $start = 1, int $step = 1, int $limit = \PHP_INT_MAX)
    {
        parent::__construct($limit);
        
        if ($step === 0) {
            throw InvalidParamException::byName('step');
        }
        
        $this->start = $start;
        $this->step = $step;
    }
    
    public function getIterator(): \Generator
    {
        $count = -1;
        $limit = $this->limit - 1;
        $current = $this->start;
        
        while ($count !== $limit) {
            yield ++$count => $current;
            
            $current += $this->step;
        }
    }
}