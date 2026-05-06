<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic\Field;

use FiiSoft\Jackdaw\Comparator\Basic\Field\Single\FieldAscComparator;
use FiiSoft\Jackdaw\Comparator\Basic\Field\Single\FieldDescComparator;
use FiiSoft\Jackdaw\Comparator\Basic\FieldComparator;
use FiiSoft\Jackdaw\Comparator\Exception\ComparatorExceptionFactory;

abstract class SingleFieldComparator extends FieldComparator
{
    /** @var string|int */
    protected $field;
    
    /**
     * @param string|int $field optional format: "id asc", "name desc"
     */
    final public static function create($field): self
    {
        $normalized = self::normalizeField($field);
        
        if ($normalized === false) {
            throw ComparatorExceptionFactory::paramFieldIsInvalid();
        }
        
        [$field, $sortAsc] = $normalized;
        
        return $sortAsc ? new FieldAscComparator($field) : new FieldDescComparator($field);
    }
    
    /**
     * @param string|int $field
     */
    final protected function __construct($field)
    {
        $this->field = $field;
    }
}