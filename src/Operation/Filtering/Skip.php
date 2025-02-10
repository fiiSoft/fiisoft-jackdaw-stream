<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Operation\Filtering\Skip\ConstantSkip;
use FiiSoft\Jackdaw\Operation\Filtering\Skip\VolatileSkip;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use FiiSoft\Jackdaw\ValueRef\IntValue;

abstract class Skip extends BaseOperation
{
    final public static function create(IntValue $offset): Skip
    {
        return $offset->isConstant()
            ? new ConstantSkip($offset->int())
            : new VolatileSkip($offset);
    }

    protected function __construct()
    {
    }
    
    final public function mergeWith(Skip $other): Skip
    {
        return self::create(IntNum::addArgs($this->offset(), $other->offset()));
    }
    
    abstract protected function offset(): IntValue;
}