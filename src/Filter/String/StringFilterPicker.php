<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterPicker;

interface StringFilterPicker
{
    public function isString(): Filter;
    
    public function length(): LengthFilterPicker;
    
    public function not(): StringFilterPicker;
    
    public function is(string $value, bool $ignoreCase = false): StringFilter;
    
    public function isNot(string $value, bool $ignoreCase = false): StringFilter;
    
    public function startsWith(string $value, bool $ignoreCase = false): StringFilter;
    
    public function notStartsWith(string $value, bool $ignoreCase = false): StringFilter;
    
    public function endsWith(string $value, bool $ignoreCase = false): StringFilter;
    
    public function notEndsWith(string $value, bool $ignoreCase = false): StringFilter;
    
    public function contains(string $value, bool $ignoreCase = false): StringFilter;
    
    public function notContains(string $value, bool $ignoreCase = false): StringFilter;
    
    /**
     * @param string[] $values
     */
    public function inSet(array $values, bool $ignoreCase = false): StringFilter;
    
    /**
     * @param string[] $values
     */
    public function notInSet(array $values, bool $ignoreCase = false): StringFilter;
}