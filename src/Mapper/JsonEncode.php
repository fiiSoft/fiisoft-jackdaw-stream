<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

final class JsonEncode implements Mapper
{
    private int $flags;
    
    public function __construct(int $flags = 0)
    {
        $this->flags = $flags;
    }
    
    public function map($value, $key)
    {
        return \json_encode($value, \JSON_THROW_ON_ERROR | $this->flags);
    }
}