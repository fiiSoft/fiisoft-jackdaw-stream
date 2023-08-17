<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class FieldValue extends BaseMapper
{
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     */
    public function __construct($field)
    {
        if (Helper::isFieldValid($field)) {
            $this->field = $field;
        } else {
            throw new \InvalidArgumentException('Invalid param field');
        }
    }
    
    public function map($value, $key)
    {
        if (\is_array($value)) {
            if (!\array_key_exists($this->field, $value)) {
                throw new \RuntimeException('Cannot extract value of field '.$this->field);
            }
        } elseif ($value instanceof \ArrayAccess) {
            if (!isset($value[$this->field])) {
                throw new \RuntimeException('Cannot extract value of field '.$this->field);
            }
        } else {
            throw new \LogicException(
                'It is impossible to extract field '.$this->field.' from '.Helper::typeOfParam($value)
            );
        }
        
        return $value[$this->field];
    }
    
    public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof self) {
            $this->field = $other->field;
            return true;
        }
        
        return false;
    }
}