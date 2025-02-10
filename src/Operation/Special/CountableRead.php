<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\ValueRef\Exception\WrongIntValueException;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use FiiSoft\Jackdaw\ValueRef\IntProvider;
use FiiSoft\Jackdaw\ValueRef\IntValue;

abstract class CountableRead extends SwapHead
{
    protected IntValue $howMany;
    
    /**
     * @param IntProvider|\Traversable<int>|iterable<int>|callable|int $howMany
     */
    protected function __construct($howMany)
    {
        $howMany = IntNum::getAdapter($howMany);
        
        if ($howMany->isConstant() && $howMany->int() < 0) {
            throw WrongIntValueException::invalidNumber($howMany);
        }
        
        $this->howMany = $howMany;
    }
    
    final public function getHowMany(): int
    {
        return $this->howMany->int();
    }
    
    final public function howManyIsConstant(): bool
    {
        return $this->howMany->isConstant();
    }
    
    final public function howManyIsConstantZero(): bool
    {
        return $this->howMany->isConstant() && $this->howMany->int() === 0;
    }
}