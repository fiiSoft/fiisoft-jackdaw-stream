<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Mapper\Mapper;

trait FieldCastMapper
{
    protected array $fields;
    
    /**
     * @param array|string|int $fields
     */
    final protected function __construct($fields)
    {
        if ($fields === null || $fields === []) {
            throw InvalidParamException::describe('fields', $fields);
        }
        
        $this->fields = \is_array($fields) ? $fields : [$fields];
    }
    
    final protected function isStateless(): bool
    {
        //@codeCoverageIgnoreStart
        return false;
        //@codeCoverageIgnoreEnd
    }
    
    final public function equals(Mapper $other): bool
    {
        return $other instanceof $this && $this->fields === $other->fields;
    }
    
    final public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof $this) {
            $this->fields = \array_unique(\array_merge($this->fields, $other->fields), \SORT_REGULAR);
            return true;
        }
        
        return false;
    }
}