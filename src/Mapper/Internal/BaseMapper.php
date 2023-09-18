<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Mapper\Mapper;

abstract class BaseMapper implements Mapper
{
    protected bool $itIsValueMapper = true;
    
    /**
     * @inheritDoc
     */
    public function mergeWith(Mapper $other): bool
    {
        return false;
    }
    
    public function isStateless(): bool
    {
        return false;
    }
    
    public function makeKeyMapper(): Mapper
    {
        $copy = clone $this;
        $copy->itIsValueMapper = false;
        
        return $copy;
    }
}