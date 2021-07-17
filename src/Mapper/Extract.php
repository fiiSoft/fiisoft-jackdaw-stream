<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;

final class Extract implements Mapper
{
    /** @var array|string|int */
    private $fields;
    
    /** @var mixed|null */
    private $orElse;
    
    /** @var bool */
    private $single;
    
    /**
     * @param array|string|int $fields
     * @param mixed|null $orElse
     */
    public function __construct($fields, $orElse = null)
    {
        if (!$this->isParamFieldsValid($fields)) {
            throw new \InvalidArgumentException('Invalid param field');
        }
        
        $this->fields = $fields;
        $this->orElse = $orElse;
        
        $this->single = !\is_array($this->fields);
    }
    
    public function map($value, $key)
    {
        if (\is_array($value) || $value instanceof \ArrayAccess) {
            if ($this->single) {
                return $value[$this->fields] ?? $this->orElse;
            }
            
            $result = [];
            foreach ($this->fields as $field) {
                $result[$field] = $value[$field] ?? $this->orElse;
            }
    
            return $result;
        }
    
        throw new \LogicException('It is impossible to extract field(s) from '.Helper::typeOfParam($value));
    }
    
    private function isParamFieldsValid($fields): bool
    {
        return \is_scalar($fields) || (\is_array($fields) && !empty($fields));
    }
}