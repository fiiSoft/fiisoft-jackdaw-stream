<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\CastMapper;

final class ToFloat extends CastMapper
{
    public function map($value, $key)
    {
        if ($this->simple) {
            if (\is_scalar($value) || $value === null) {
                return (float) $value;
            }
        
            throw new \LogicException('Unable to cast to float param '.Helper::typeOfParam($value));
        }
    
        if (\is_array($value) || $value instanceof \ArrayAccess) {
            foreach ($this->fields as $field) {
                $value[$field] = (float) $value[$field];
            }
        
            return $value;
        }
    
        throw new \LogicException('Unable to cast to float param '.Helper::typeOfParam($value));
    }
}