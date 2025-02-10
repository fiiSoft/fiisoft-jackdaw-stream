<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Picker;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterPicker;
use FiiSoft\Jackdaw\Filter\String\StringFilter;
use FiiSoft\Jackdaw\Filter\String\StringFilterPicker;
use FiiSoft\Jackdaw\Filter\ValRef\FilterAdapterFactory;
use FiiSoft\Jackdaw\Filter\ValRef\FilterPicker;

final class StringPicker implements StringFilterPicker
{
    private FilterAdapterFactory $factory;
    private bool $isNot;
    
    public function __construct(FilterAdapterFactory $factory, bool $isNot = false)
    {
        $this->factory = $factory;
        $this->isNot = $isNot;
    }
    
    public function isString(): Filter
    {
        return $this->picker()->type()->isString();
    }
    
    public function length(): LengthFilterPicker
    {
        return $this->picker()->length();
    }
    
    public function not(): StringFilterPicker
    {
        return new self($this->factory, !$this->isNot);
    }
    
    public function is(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->createFilter(Filters::string()->is($value, $ignoreCase));
    }
    
    public function isNot(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->createFilter(Filters::string()->isNot($value, $ignoreCase));
    }
    
    public function startsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->createFilter(Filters::string()->startsWith($value, $ignoreCase));
    }
    
    public function notStartsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->createFilter(Filters::string()->notStartsWith($value, $ignoreCase));
    }
    
    public function endsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->createFilter(Filters::string()->endsWith($value, $ignoreCase));
    }
    
    public function notEndsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->createFilter(Filters::string()->notEndsWith($value, $ignoreCase));
    }
    
    public function contains(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->createFilter(Filters::string()->contains($value, $ignoreCase));
    }
    
    public function notContains(string $value, bool $ignoreCase = false): StringFilter
    {
        return $this->createFilter(Filters::string()->notContains($value, $ignoreCase));
    }
    
    /**
     * @inheritDoc
     */
    public function inSet(array $values, bool $ignoreCase = false): StringFilter
    {
        return $this->createFilter(Filters::string()->inSet($values, $ignoreCase));
    }
    
    /**
     * @inheritDoc
     */
    public function notInSet(array $values, bool $ignoreCase = false): StringFilter
    {
        return $this->createFilter(Filters::string()->notInSet($values, $ignoreCase));
    }
    
    private function createFilter(StringFilter $filter): StringFilter
    {
        $adapter = $this->factory->createStringFilter($filter);
        
        return $this->isNot ? $adapter->negate() : $adapter;
    }
    
    private function picker(): FilterPicker
    {
        return $this->factory->getPicker($this->isNot);
    }
}