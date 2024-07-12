<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic;

use FiiSoft\Jackdaw\Comparator\Exception\ComparatorExceptionFactory;

final class FieldsComparator extends BaseComparator
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
            if (\is_string($field)) {
                $field = \trim($field);
                if ($field !== '') {
                    if (\substr_compare($field, ' desc', -5, null, true) === 0) {
                        $field = \trim(\substr($field, 0, -5));
                        $direction = 'desc';
                    } elseif (\substr_compare($field, ' asc', -4, null, true) === 0) {
                        $field = \trim(\substr($field, 0, -4));
                        $direction = 'asc';
                    } else {
                        $direction = 'asc';
                    }
    
                    $this->fields[$field] = $direction === 'asc';
                    continue;
                }
            } elseif (\is_int($field)) {
                $this->fields[$field] = true;
                continue;
            }
            
            throw ComparatorExceptionFactory::paramFieldsIsInvalid();
        }
    }
    
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        throw ComparatorExceptionFactory::compareAssocIsNotImplemented();
    }
}