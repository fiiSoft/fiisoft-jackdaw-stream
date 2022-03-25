<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Simple extends BaseMapper
{
    /** @var mixed */
    private $value;
    
    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
    
    public function map($value, $key)
    {
        return $this->value;
    }
    
    public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof self) {
            $this->value = $other->value;
            return true;
        }
        
        return false;
    }
}