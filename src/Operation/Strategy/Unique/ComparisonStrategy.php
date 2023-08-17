<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Internal\Destroyable;

abstract class ComparisonStrategy implements Destroyable
{
    /**
     * @param mixed $value
     */
    abstract public function isUnique($value): bool;
    
    /**
     * @param mixed $value
     */
    abstract public function remember($value): void;
}