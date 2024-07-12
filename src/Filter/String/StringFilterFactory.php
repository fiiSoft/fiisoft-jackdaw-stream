<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterFactory;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterFactory;

final class StringFilterFactory extends FilterFactory
{
    public static function instance(?int $mode = null): self
    {
        return new self($mode);
    }
    
    public function isString(): Filter
    {
        return $this->get(Filters::isString($this->mode));
    }
    
    public function length(): LengthFilterFactory
    {
        return Filters::length($this->mode);
    }
    
    public function is(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->getStringFilter(StrIs::create($this->mode, $value, $ignoreCase));
    }
    
    public function isNot(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->getStringFilter(StrIsNot::create($this->mode, $value, $ignoreCase));
    }
    
    public function contains(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->getStringFilter(Contains::create($this->mode, $value, $ignoreCase));
    }
    
    public function notContains(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->getStringFilter(NotContains::create($this->mode, $value, $ignoreCase));
    }
    
    public function startsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->getStringFilter(StartsWith::create($this->mode, $value, $ignoreCase));
    }
    
    public function notStartsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->getStringFilter(NotStartsWith::create($this->mode, $value, $ignoreCase));
    }
    
    public function endsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->getStringFilter(EndsWith::create($this->mode, $value, $ignoreCase));
    }
    
    public function notEndsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->getStringFilter(NotEndsWith::create($this->mode, $value, $ignoreCase));
    }
    
    /**
     * @param string[] $values
     */
    public function inSet(array $values, bool $ignoreCase = false): StringFilter
    {
        return $this->getStringFilter(InSet::create($this->mode, $values, $ignoreCase));
    }
    
    /**
     * @param string[] $values
     */
    public function notInSet(array $values, bool $ignoreCase = false): StringFilter
    {
        return $this->getStringFilter(NotInSet::create($this->mode, $values, $ignoreCase));
    }
    
    private function getStringFilter(StringFilter $filter): StringFilter
    {
        return $this->isNot ? $filter->negate() : $filter;
    }
}