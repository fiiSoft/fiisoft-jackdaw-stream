<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic\Field;

use FiiSoft\Jackdaw\Comparator\Basic\FieldComparator;
use FiiSoft\Jackdaw\Comparator\Exception\ComparatorExceptionFactory;

final class MultiFieldsComparator extends FieldComparator
{
    /** @var array<string|int, bool> */
    private array $fields = [];
    
    /**
     * @param array<string|int> $fields format: "id asc", "name desc"
     */
    public function __construct(array $fields)
    {
        $this->validateAndSetFields($fields);
    }
    
    /**
     * @inheritDoc
     */
    public function compare($value1, $value2): int
    {
        foreach ($this->fields as $field => $sortAsc) {
            $compare = \gettype($value1[$field]) <=> \gettype($value2[$field]) ?: $value1[$field] <=> $value2[$field];
            if ($compare !== 0) {
                return $sortAsc ? $compare : -$compare;
            }
        }
        
        return 0;
    }
    
    /**
     * @param array<string|int> $fields format: "id asc", "name desc"
     */
    private function validateAndSetFields(array $fields): void
    {
        if (empty($fields)) {
            throw ComparatorExceptionFactory::paramFieldsCannotBeEmpty();
        }
    
        foreach ($fields as $field) {
            $normalized = self::normalizeField($field);
            
            if ($normalized === false) {
                throw ComparatorExceptionFactory::paramFieldsIsInvalid();
            }
            
            [$field, $sortAsc] = $normalized;
            
            $this->fields[$field] = $sortAsc;
        }
    }
}