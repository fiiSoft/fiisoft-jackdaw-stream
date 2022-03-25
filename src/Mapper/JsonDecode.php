<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class JsonDecode extends BaseMapper
{
    private bool $associative;
    private int $flags;
    
    public function __construct(int $flags = 0, bool $associative = true)
    {
        $this->associative = $associative;
        $this->flags = $flags;
    }
    
    public function map($value, $key)
    {
        if (\is_string($value)) {
            return \json_decode($value, $this->associative, 512, \JSON_THROW_ON_ERROR | $this->flags);
        }
        
        throw new \LogicException('You cannot decode '.Helper::typeOfParam($value).' to JSON');
    }
}