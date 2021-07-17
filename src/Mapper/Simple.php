<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

final class Simple implements Mapper
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
}