<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer\Reference\ChangeInt;

use FiiSoft\Jackdaw\Consumer\Reference\ChangeIntRef;

final class ConstantChangeInt extends ChangeIntRef
{
    private int $value;
    
    /**
     * @param int $variable REFERENCE
     */
    protected function __construct(int &$variable, int $value)
    {
        parent::__construct($variable);
        
        $this->value = $value;
    }
    
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        $this->variable += $this->value;
    }
}