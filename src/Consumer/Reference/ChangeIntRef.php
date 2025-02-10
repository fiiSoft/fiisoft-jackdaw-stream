<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer\Reference;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\Reference\ChangeInt\ConstantChangeInt;
use FiiSoft\Jackdaw\Consumer\Reference\ChangeInt\VolatileChangeInt;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use FiiSoft\Jackdaw\ValueRef\IntProvider;

abstract class ChangeIntRef implements Consumer
{
    /** @var int REFERENCE */
    protected int $variable;
    
    /**
     * @param int|null $variable REFERENCE
     * @param IntProvider|\Traversable<int>|iterable<int>|callable|int $value
     * @param-out int $variable
     */
    final public static function create(?int &$variable, $value): ChangeIntRef
    {
        if ($variable === null) {
            $variable = 0;
        }
        
        $value = IntNum::getAdapter($value);
        
        return $value->isConstant()
            ? new ConstantChangeInt($variable, $value->int())
            : new VolatileChangeInt($variable, $value);
    }
    
    /**
     * @param int $variable REFERENCE
     */
    protected function __construct(int &$variable)
    {
        $this->variable = &$variable;
    }
}