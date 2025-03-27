<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Helper;

final class ReorderKeys extends Internal\StateMapper
{
    /** @var array<string|int> */
    private array $fields;
    
    /**
     * @param array<string|int> $fields
     */
    public function __construct(array $fields)
    {
        if (Helper::areFieldsValid($fields)) {
            $this->fields = $fields;
        } else {
            throw InvalidParamException::describe('fields', $fields);
        }
    }
    
    /**
     * @return array<string|int, mixed>
     */
    public function map($value, $key = null): array
    {
        $result = [];
        
        foreach ($this->fields as $field) {
            $result[$field] = $value[$field];
            unset($value[$field]);
        }
        
        foreach ($value as $k => $v) {
            $result[$k] = $v;
        }
        
        return $result;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $result = [];
            
            foreach ($this->fields as $field) {
                $result[$field] = $value[$field];
                unset($value[$field]);
            }
            
            foreach ($value as $k => $v) {
                $result[$k] = $v;
            }
            
            yield $key => $result;
        }
    }
}