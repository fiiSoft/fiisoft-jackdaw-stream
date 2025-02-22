<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer\Reference\ChangeInt;

use FiiSoft\Jackdaw\Consumer\Reference\ChangeIntRef;
use FiiSoft\Jackdaw\ValueRef\IntValue;

final class VolatileChangeInt extends ChangeIntRef
{
    private IntValue $value;
    
    /**
     * @param int $variable REFERENCE
     */
    protected function __construct(int &$variable, IntValue $value)
    {
        parent::__construct($variable);
        
        $this->value = $value;
    }
    
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        $this->variable += $this->value->int();
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->variable += $this->value->int();
            
            yield $key => $value;
        }
    }
}