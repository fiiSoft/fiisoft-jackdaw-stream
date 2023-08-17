<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class MapField extends BaseMapper
{
    private Mapper $mapper;
    
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     */
    public function __construct($field, Mapper $mapper)
    {
        if (Helper::isFieldValid($field)) {
            $this->field = $field;
        } else {
            throw new \InvalidArgumentException('Invalid param field');
        }
        
        $this->mapper = $mapper;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if (\is_array($value)) {
            if (!\array_key_exists($this->field, $value)) {
                throw new \RuntimeException('Field '.$this->field.' does not exist in value');
            }
        } elseif ($value instanceof \ArrayAccess) {
            if (!isset($value[$this->field])) {
                throw new \RuntimeException('Field '.$this->field.' does not exist in value');
            }
        } else {
            throw new \LogicException(
                'Unable to map field '.$this->field.' because value is '.Helper::typeOfParam($value)
            );
        }
        
        $value[$this->field] = $this->mapper->map($value[$this->field], $key);
        
        return $value;
    }
}