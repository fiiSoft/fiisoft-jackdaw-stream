<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\CastMapper;

final class ToInt extends CastMapper
{
    public function map($value, $key)
    {
        if ($this->simple) {
            if (\is_scalar($value) || $value === null) {
                return (int) $value;
            }
            
            throw new \LogicException('Unable to cast to int param '.Helper::typeOfParam($value));
        }
        
        if (\is_array($value) || $value instanceof \ArrayAccess) {
            foreach ($this->fields as $field) {
                $value[$field] = (int) $value[$field];
            }
            
            return $value;
        }
        
        throw new \LogicException('Unable to cast to int param '.Helper::typeOfParam($value));
    }
}