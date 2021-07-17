<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;

final class Reverse implements Mapper
{
    public function map($value, $key)
    {
        if (\is_array($value)) {
            return \array_reverse($value);
        }
    
        if (\is_string($value)) {
            return \strrev($value);
        }
        
        throw new \LogicException('Unable to reverse '.Helper::typeOfParam($value));
    }
}