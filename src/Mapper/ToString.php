<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\CastMapper;

final class ToString extends CastMapper
{
    public function map($value, $key)
    {
        if ($this->simple) {
            if (\is_scalar($value) || $value === null) {
                return (string) $value;
            }
    
            throw new \LogicException('Unable to cast to string param '.Helper::typeOfParam($value));
        }
        
        if (\is_array($value) || $value instanceof \ArrayAccess) {
            foreach ($this->fields as $field) {
                $value[$field] = (string) $value[$field];
            }
            
            return $value;
        }
        
        throw new \LogicException(
            'Unable to cast to string param '.Helper::typeOfParam($value).' when array is required'
        );
    }
}