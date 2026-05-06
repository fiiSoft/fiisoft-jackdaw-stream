<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic;

use FiiSoft\Jackdaw\Comparator\Exception\ComparatorExceptionFactory;

abstract class FieldComparator extends BaseComparator
{
    /**
     * @inheritDoc
     */
    final public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        throw ComparatorExceptionFactory::compareAssocIsNotImplemented();
    }
    
    /**
     * @param string|int $field
     * @return array{string|int, bool}|false tuple or false
     */
    final protected static function normalizeField($field)
    {
        if (\is_string($field)) {
            $field = \trim($field);
            
            if ($field !== '') {
                $sortAsc = true;
                
                if (\substr_compare($field, ' desc', -5, null, true) === 0) {
                    $field = \trim(\substr($field, 0, -5));
                    $sortAsc = false;
                } elseif (\substr_compare($field, ' asc', -4, null, true) === 0) {
                    $field = \trim(\substr($field, 0, -4));
                }
                
                return [$field, $sortAsc];
            }
        } elseif (\is_int($field)) {
            return [$field, true];
        }
        
        return false;
    }
}