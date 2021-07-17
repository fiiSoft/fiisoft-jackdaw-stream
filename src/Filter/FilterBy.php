<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;

final class FilterBy implements Filter
{
    /** @var Filter */
    private $filter;
    
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     * @param Filter $filter
     */
    public function __construct($field, Filter $filter)
    {
        if (\is_string($field) || \is_int($field)) {
            $this->field = $field;
        } else {
            throw new \InvalidArgumentException('Invalid param field');
        }
        
        $this->filter = $filter;
    }
    
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        if (\is_array($value) || $value instanceof \ArrayAccess) {
            if (\array_key_exists($this->field, $value)) {
                return $this->filter->isAllowed($value[$this->field], $key, $mode);
            }
            
            throw new \RuntimeException('ByField '.$this->field.' does not exist in value');
        }
    
        throw new \LogicException(
            'Unable to filter by '.$this->field.' because value is '.Helper::typeOfParam($value)
        );
    }
}