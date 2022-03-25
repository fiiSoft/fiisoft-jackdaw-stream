<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Concat extends BaseMapper
{
    private string $separator;
    
    public function __construct(string $separator = '')
    {
        $this->separator = $separator;
    }
    
    public function map($value, $key)
    {
        if (\is_array($value)) {
            return \implode($this->separator, $value);
        }
    
        throw new \LogicException('Unable to concat something which is not an array');
    }
}