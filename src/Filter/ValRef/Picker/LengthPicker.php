<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Picker;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterPicker;

final class LengthPicker extends BasePicker implements LengthFilterPicker
{
    public function isString(): Filter
    {
        return $this->createFilter(Filters::isString());
    }
    
    public function not(): LengthFilterPicker
    {
        return $this->negate();
    }
    
    /**
     * @inheritDoc
     */
    public function eq(int $length): Filter
    {
        return $this->createFilter(Filters::length()->eq($length));
    }
    
    /**
     * @inheritDoc
     */
    public function ne(int $length): Filter
    {
        return $this->createFilter(Filters::length()->ne($length));
    }
    
    /**
     * @inheritDoc
     */
    public function le(int $length): Filter
    {
        return $this->createFilter(Filters::length()->le($length));
    }
    
    /**
     * @inheritDoc
     */
    public function ge(int $length): Filter
    {
        return $this->createFilter(Filters::length()->ge($length));
    }
    
    /**
     * @inheritDoc
     */
    public function gt(int $length): Filter
    {
        return $this->createFilter(Filters::length()->gt($length));
    }
    
    /**
     * @inheritDoc
     */
    public function lt(int $length): Filter
    {
        return $this->createFilter(Filters::length()->lt($length));
    }
    
    /**
     * @inheritDoc
     */
    public function inside(int $min, int $max): Filter
    {
        return $this->createFilter(Filters::length()->inside($min, $max));
    }
    
    /**
     * @inheritDoc
     */
    public function outside(int $min, int $max): Filter
    {
        return $this->createFilter(Filters::length()->outside($min, $max));
    }
    
    /**
     * @inheritDoc
     */
    public function between(int $min, int $max): Filter
    {
        return $this->createFilter(Filters::length()->between($min, $max));
    }
    
    /**
     * @inheritDoc
     */
    public function notInside(int $min, int $max): Filter
    {
        return $this->createFilter(Filters::length()->notInside($min, $max));
    }
}