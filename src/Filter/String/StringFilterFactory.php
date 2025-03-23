<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterFactory;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterPicker;

final class StringFilterFactory extends FilterFactory implements StringFilterPicker
{
    public function isString(): Filter
    {
        return $this->get(Filters::isString($this->mode));
    }
    
    public function length(): LengthFilterPicker
    {
        return Filters::length($this->mode);
    }
    
    public function not(): StringFilterPicker
    {
        return $this->negate();
    }
    
    public function is(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->create(StrIs::create($this->mode, $value, $ignoreCase));
    }
    
    public function isNot(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->create(StrIsNot::create($this->mode, $value, $ignoreCase));
    }
    
    public function contains(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->create(Contains::create($this->mode, $value, $ignoreCase));
    }
    
    public function notContains(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->create(NotContains::create($this->mode, $value, $ignoreCase));
    }
    
    public function startsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->create(StartsWith::create($this->mode, $value, $ignoreCase));
    }
    
    public function notStartsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->create(NotStartsWith::create($this->mode, $value, $ignoreCase));
    }
    
    public function endsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->create(EndsWith::create($this->mode, $value, $ignoreCase));
    }
    
    public function notEndsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->create(NotEndsWith::create($this->mode, $value, $ignoreCase));
    }
    
    /**
     * @param string[] $values
     */
    public function inSet(array $values, bool $ignoreCase = false): StringFilter
    {
        return $this->create(InSet::create($this->mode, $values, $ignoreCase));
    }
    
    /**
     * @param string[] $values
     */
    public function notInSet(array $values, bool $ignoreCase = false): StringFilter
    {
        return $this->create(NotInSet::create($this->mode, $values, $ignoreCase));
    }
    
    private function create(AbstractStringFilter $filter): StringFilter
    {
        return $this->isNot ? $filter->negate() : $filter;
    }
}