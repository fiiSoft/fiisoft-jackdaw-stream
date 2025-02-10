<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Picker;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Size\Count\CountFilterPicker;

final class CountPicker extends BasePicker implements CountFilterPicker
{
    public function isCountable(): Filter
    {
        return $this->picker()->type()->isCountable();
    }
    
    public function not(): CountFilterPicker
    {
        return $this->negate();
    }
    
    /**
     * @inheritDoc
     */
    public function eq(int $size): Filter
    {
        return $this->createFilter(Filters::size()->eq($size));
    }
    
    /**
     * @inheritDoc
     */
    public function ne(int $size): Filter
    {
        return $this->createFilter(Filters::size()->ne($size));
    }
    
    /**
     * @inheritDoc
     */
    public function le(int $size): Filter
    {
        return $this->createFilter(Filters::size()->le($size));
    }
    
    /**
     * @inheritDoc
     */
    public function ge(int $size): Filter
    {
        return $this->createFilter(Filters::size()->ge($size));
    }
    
    /**
     * @inheritDoc
     */
    public function lt(int $size): Filter
    {
        return $this->createFilter(Filters::size()->lt($size));
    }
    
    /**
     * @inheritDoc
     */
    public function gt(int $size): Filter
    {
        return $this->createFilter(Filters::size()->gt($size));
    }
    
    /**
     * @inheritDoc
     */
    public function inside(int $min, int $max): Filter
    {
        return $this->createFilter(Filters::size()->inside($min, $max));
    }
    
    /**
     * @inheritDoc
     */
    public function notInside(int $min, int $max): Filter
    {
        return $this->createFilter(Filters::size()->notInside($min, $max));
    }
    
    /**
     * @inheritDoc
     */
    public function between(int $min, int $max): Filter
    {
        return $this->createFilter(Filters::size()->between($min, $max));
    }
    
    /**
     * @inheritDoc
     */
    public function outside(int $min, int $max): Filter
    {
        return $this->createFilter(Filters::size()->outside($min, $max));
    }
}