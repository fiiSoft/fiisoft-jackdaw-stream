<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Exception\MapperExceptionFactory;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class Remove extends StateMapper
{
    private array $fields;
    
    /**
     * @param array|string|int $fields
     */
    public function __construct($fields)
    {
        if (Helper::areFieldsValid($fields)) {
            $this->fields = \array_flip(\is_array($fields) ? $fields : [$fields]);
        } else {
            throw InvalidParamException::describe('fields', $fields);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
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
    
        throw MapperExceptionFactory::unsupportedValue($value);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_array($value)) {
                yield $key => \array_diff_key($value, $this->fields);
            } elseif ($value instanceof \ArrayAccess) {
                foreach ($this->fields as $field => $_) {
                    unset($value[$field]);
                }
                
                yield $key => $value;
            } else {
                throw MapperExceptionFactory::unsupportedValue($value);
            }
        }
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