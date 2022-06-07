<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Remove extends BaseMapper
{
    private array $fields;
    
    /**
     * @param array|string|int $fields
     */
    public function __construct($fields)
    {
        if (!Helper::areFieldsValid($fields)) {
            throw new \InvalidArgumentException('Invalid param fields');
        }
    
        $this->fields = \array_flip(\is_array($fields) ? $fields : [$fields]);
    }
    
    public function map($value, $key)
    {
        if (\is_array($value)) {
            return \array_diff_key($value, $this->fields);
        }
    
        if ($value instanceof \ArrayAccess) {
            foreach ($this->fields as $field => $_) {
                unset($value[$field]);
            }
            
            return $value;
        }
    
        throw new \LogicException('Unsupported '.Helper::typeOfParam($value).' as value in Remove mapper');
    }
    
    public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof self) {
            $this->fields += $other->fields;
            return true;
        }
        
        return false;
    }
}