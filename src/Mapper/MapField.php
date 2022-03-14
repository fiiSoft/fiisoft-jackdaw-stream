<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;

final class MapField implements Mapper
{
    private Mapper $mapper;
    
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     * @param Mapper $mapper
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
        if (\is_array($value) || $value instanceof \ArrayAccess) {
            if (\array_key_exists($this->field, $value)) {
                $value[$this->field] = $this->mapper->map($value[$this->field], $key);
                return $value;
            }
    
            throw new \RuntimeException('Field '.$this->field.' does not exist in value');
        }
    
        throw new \LogicException(
            'Unable to map field '.$this->field.' because value is '.Helper::typeOfParam($value)
        );
    }
}