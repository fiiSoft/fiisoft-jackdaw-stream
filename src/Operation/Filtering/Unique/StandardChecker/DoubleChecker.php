<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker;

use FiiSoft\Jackdaw\Operation\Filtering\Unique\ComparisonStrategy;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\UniquenessChecker;

abstract class DoubleChecker implements UniquenessChecker
{
    protected ComparisonStrategy $key;
    protected ComparisonStrategy $value;
    
    final public function __construct(ComparisonStrategy $strategy)
    {
        $this->value = $strategy;
        $this->key = clone $strategy;
    }
    
    final public function destroy(): void
    {
        $this->value->destroy();
        $this->key->destroy();
    }
}