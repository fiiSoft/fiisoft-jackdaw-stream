<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;

final class ToFloat implements Mapper
{
    /** @var array|null */
    private $fields = null;
    
    /** @var bool */
    private $simple;
    
    /**
     * @param array|string|int|null $fields
     */
    public function __construct($fields = null)
    {
        if ($fields !== null) {
            if (\is_array($fields)) {
                if (empty($fields)) {
                    throw new \InvalidArgumentException('Param fields is invalid');
                }
            
                $this->fields = $fields;
            } else {
                $this->fields = [$fields];
            }
        }
    
        $this->simple = $this->fields === null;
    }
    
    public function map($value, $key)
    {
        if ($this->simple) {
            if ($value === null || \is_scalar($value)) {
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