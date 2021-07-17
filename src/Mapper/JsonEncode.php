<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

final class JsonEncode implements Mapper
{
    /** @var int */
    private $flags;
    
    public function __construct(int $flags = 0)
    {
        $this->flags = $flags;
    }
    
    public function map($value, $key)
    {
        return \json_encode($value, $this->flags);
    }
}