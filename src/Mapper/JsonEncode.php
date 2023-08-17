<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class JsonEncode extends BaseMapper
{
    private int $flags;
    
    public function __construct(int $flags = 0)
    {
        $this->flags = $flags;
    }
    
    public function map($value, $key): string
    {
        return \json_encode($value, \JSON_THROW_ON_ERROR | $this->flags);
    }
}