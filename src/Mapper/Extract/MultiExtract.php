<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Extract;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Extract;

final class MultiExtract extends Extract
{
    /** @var array<string|int> */
    private array $fields;
    
    /**
     * @param mixed|null $orElse
     */
    protected function __construct(array $fields, $orElse = null)
    {
        parent::__construct($orElse);
        
        if (Helper::areFieldsValid($fields)) {
            $this->fields = $fields;
        } else {
            throw InvalidParamException::describe('fields', $fields);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null): array
    {
        $result = [];
        
        foreach ($this->fields as $field) {
            $result[$field] = $value[$field] ?? $this->orElse;
        }
        
        return $result;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $result = [];
            
            foreach ($this->fields as $field) {
                $result[$field] = $value[$field] ?? $this->orElse;
            }
            
            yield $key => $result;
        }
    }
}