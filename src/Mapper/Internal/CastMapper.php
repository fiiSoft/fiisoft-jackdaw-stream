<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Mapper\Mapper;

abstract class CastMapper extends BaseMapper
{
    protected ?array $fields = null;
    protected bool $simple;
    
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
    
    final public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof $this && $other->simple === $this->simple && $other->fields !== null) {
            $this->fields = \array_unique(\array_merge($this->fields, $other->fields), \SORT_REGULAR);
            return true;
        }
        
        return false;
    }
}