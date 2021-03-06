<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Extract extends BaseMapper
{
    /** @var array|string|int */
    private $fields;
    
    /** @var mixed|null */
    private $orElse;
    
    private bool $single;
    
    /**
     * @param array|string|int $fields
     * @param mixed|null $orElse
     */
    public function __construct($fields, $orElse = null)
    {
        if (!Helper::areFieldsValid($fields)) {
            throw new \InvalidArgumentException('Invalid param fields');
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
}