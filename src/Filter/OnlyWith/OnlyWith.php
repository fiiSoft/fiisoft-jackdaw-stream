<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyWith;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\BaseFilter;
use FiiSoft\Jackdaw\Filter\Exception\FilterExceptionFactory;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

abstract class OnlyWith extends BaseFilter
{
    protected array $fields;
    
    /**
     * It only passes array (or \ArrayAccess) values containing the specified field(s).
     * Currently, only VALUE mode is supported and attempting to change it will result in an exception.
     *
     * @param array|string|int $fields
     */
    final public static function create($fields, bool $allowNulls): self
    {
        return $allowNulls ? new OnlyWithAllowNulls($fields) : new OnlyWithDisallowNulls($fields);
    }
    
    /**
     * @param array|string|int $fields
     */
    final protected function __construct($fields)
    {
        parent::__construct(Check::VALUE);
        
        if (\is_array($fields)) {
            if (empty($fields)) {
                throw FilterExceptionFactory::paramFieldsCannotBeEmpty();
            }
        } elseif ((\is_string($fields) && $fields !== '') || \is_int($fields)) {
            $fields = [$fields];
        } else {
            throw InvalidParamException::describe('fields', $fields);
        }
        
        $this->fields = $fields;
    }
    
    final public function negate(): Filter
    {
        return $this->createDefaultNOT();
    }
    
    final public function inMode(?int $mode): Filter
    {
        if ($mode !== null && $mode !== $this->mode) {
            throw FilterExceptionFactory::modeNotSupportedYet($mode);
        }
        
        return $this;
    }
}