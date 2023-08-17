<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator;

final class FieldsComparator implements Comparator
{
    /** @var string[]|int[] */
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
        if (!\is_array($value1) && !$value1 instanceof \ArrayAccess
            || !\is_array($value2) && !$value2 instanceof \ArrayAccess
        ) {
            throw new \LogicException('FieldsComparator comparator can compare only arrays');
        }
    
        foreach ($this->fields as $field => $sortAsc) {
            $compare = $value1[$field] <=> $value2[$field];
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
            throw new \InvalidArgumentException('Fields cannot be empty');
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
            
            throw new \InvalidArgumentException(
                'Each element of array fields have to be a non empty string or integer'
            );
        }
    }
    
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        throw new \LogicException('Sorry, this comparision is not implemented, and never will be');
    }
}