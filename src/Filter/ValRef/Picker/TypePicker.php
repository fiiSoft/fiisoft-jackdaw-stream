<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Picker;

use FiiSoft\Jackdaw\Filter\CheckType\TypeFilterPicker;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;

final class TypePicker extends BasePicker implements TypeFilterPicker
{
    public function isNull(): Filter
    {
        return $this->createFilter(Filters::type()->isNull());
    }
    
    public function not(): TypeFilterPicker
    {
        return $this->negate();
    }
    
    public function notNull(): Filter
    {
        return $this->createFilter(Filters::type()->notNull());
    }
    
    public function isEmpty(): Filter
    {
        return $this->createFilter(Filters::type()->isEmpty());
    }
    
    public function notEmpty(): Filter
    {
        return $this->createFilter(Filters::type()->notEmpty());
    }
    
    public function isInt(): Filter
    {
        return $this->createFilter(Filters::type()->isInt());
    }
    
    public function isNumeric(): Filter
    {
        return $this->createFilter(Filters::type()->isNumeric());
    }
    
    public function isString(): Filter
    {
        return $this->createFilter(Filters::type()->isString());
    }
    
    public function isBool(): Filter
    {
        return $this->createFilter(Filters::type()->isBool());
    }
    
    public function isFloat(): Filter
    {
        return $this->createFilter(Filters::type()->isFloat());
    }
    
    public function isArray(): Filter
    {
        return $this->createFilter(Filters::type()->isArray());
    }
    
    public function isCountable(): Filter
    {
        return $this->createFilter(Filters::type()->isCountable());
    }
    
    public function isDateTime(): Filter
    {
        return $this->createFilter(Filters::type()->isDateTime());
    }
}