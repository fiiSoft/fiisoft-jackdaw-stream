<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\StatelessMapper;

final class Reverse extends StatelessMapper
{
    public function map($value, $key)
    {
        if (\is_array($value)) {
            return \array_reverse($value, true);
        }
    
        if (\is_string($value)) {
            return \strrev($value);
        }
        
        throw new \LogicException('Unable to reverse '.Helper::typeOfParam($value));
    }
}