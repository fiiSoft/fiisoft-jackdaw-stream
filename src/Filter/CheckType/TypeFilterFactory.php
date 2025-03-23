<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterFactory;

final class TypeFilterFactory extends FilterFactory implements TypeFilterPicker
{
    public function not(): TypeFilterPicker
    {
        return $this->negate();
    }
    
    public function isNull(): Filter
    {
        return $this->get(IsNull::create($this->mode));
    }
    
    public function notNull(): Filter
    {
        return $this->get(NotNull::create($this->mode));
    }
    
    public function isEmpty(): Filter
    {
        return $this->get(IsEmpty::create($this->mode));
    }
    
    public function notEmpty(): Filter
    {
        return $this->get(NotEmpty::create($this->mode));
    }
    
    public function isInt(): Filter
    {
        return $this->get(IsInt::create($this->mode));
    }
    
    public function isNumeric(): Filter
    {
        return $this->get(IsNumeric::create($this->mode));
    }
    
    public function isString(): Filter
    {
        return $this->get(IsString::create($this->mode));
    }
    
    public function isBool(): Filter
    {
        return $this->get(IsBool::create($this->mode));
    }
    
    public function isFloat(): Filter
    {
        return $this->get(IsFloat::create($this->mode));
    }
    
    public function isArray(): Filter
    {
        return $this->get(IsArray::create($this->mode));
    }
    
    public function isCountable(): Filter
    {
        return $this->get(IsCountable::create($this->mode));
    }
    
    public function isDateTime(): Filter
    {
        return $this->get(IsDateTime::create($this->mode));
    }
}