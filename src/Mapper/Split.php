<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;

final class Split implements Mapper
{
    private string $separator;
    
    public function __construct(string $separator = '')
    {
        $this->separator = $separator;
    }
    
    public function map($value, $key)
    {
        if (\is_string($value)) {
            return \explode($this->separator, $value);
        }
        
        throw new \LogicException('It is impossible to handle '.Helper::typeOfParam($value).' as string');
    }
}