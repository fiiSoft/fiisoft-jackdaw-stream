<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\ValueRef\Adapter;

final class ReferenceToInt extends VolatileIntValue
{
    /** @var int REFERENCE */
    private int $value;
    
    /**
     * @param int|null $variable REFERENCE
     * @param-out int $variable
     */
    public function __construct(?int &$variable)
    {
        if ($variable === null) {
            $variable = 0;
        }
        
        $this->value = &$variable;
    }
    
    public function int(): int
    {
        return $this->value;
    }
}