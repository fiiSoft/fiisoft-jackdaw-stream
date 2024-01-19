<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker;

use FiiSoft\Jackdaw\Operation\Filtering\Unique\ComparisonStrategy;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker;

abstract class DoubleChecker extends StandardChecker
{
    protected ComparisonStrategy $keyStrategy;
    
    public function __construct(ComparisonStrategy $strategy)
    {
        parent::__construct($strategy);
        
        $this->keyStrategy = clone $strategy;
    }
}