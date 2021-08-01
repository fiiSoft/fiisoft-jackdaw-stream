<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

final class Complete implements Mapper
{
    private Mapper $mapper;
    
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     * @param Mapper|callable|mixed $mapper
     */
    public function __construct($field, $mapper)
    {
        if ($this->isFieldValid($field)) {
            $this->field = $field;
        } else {
            throw new \InvalidArgumentException('Invalid param field');
        }
        
        $this->mapper = Mappers::getAdapter($mapper);
    }
    
    public function map($value, $key)
    {
        if (\is_array($value) || $value instanceof \ArrayAccess) {
            if (!isset($value[$this->field])) {
                $value[$this->field] = $this->mapper->map($value, $key);
            }
            
            return $value;
        }
        
        return [
            $key => $value,
            $this->field => $this->mapper->map($value, $key),
        ];
    }
    
    private function isFieldValid($field): bool
    {
        return \is_string($field) && $field !== '' || \is_int($field);
    }
}