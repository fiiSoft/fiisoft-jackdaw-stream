<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\Operation\Special\ReadMany\ReadManyKeepKeys;
use FiiSoft\Jackdaw\Operation\Special\ReadMany\ReadManyReindexKeys;
use FiiSoft\Jackdaw\ValueRef\IntProvider;

abstract class ReadMany extends CountableRead
{
    /**
     * @param IntProvider|iterable<int>|callable|int $howMany
     */
    final public static function create($howMany, bool $reindex = false): ReadMany
    {
        return $reindex ? new ReadManyReindexKeys($howMany) : new ReadManyKeepKeys($howMany);
    }
    
    final public function howManyIsConstantOne(): bool
    {
        return $this->howMany->isConstant() && $this->howMany->int() === 1;
    }
    
    abstract public function reindexKeys(): bool;
}