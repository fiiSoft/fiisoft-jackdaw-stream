<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Picker;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Number\NumberFilterPicker;

final class NumberPicker extends BasePicker implements NumberFilterPicker
{
    public function isNumeric(): Filter
    {
        return $this->picker()->type()->isNumeric();
    }
    
    public function isFloat(): Filter
    {
        return $this->picker()->type()->isFloat();
    }
    
    public function isInt(): Filter
    {
        return $this->picker()->type()->isInt();
    }
    
    public function not(): NumberFilterPicker
    {
        return $this->negate();
    }
    
    /**
     * @inheritDoc
     */
    public function isEven(): Filter
    {
        return $this->createFilter(Filters::number()->isEven());
    }
    
    /**
     * @inheritDoc
     */
    public function isOdd(): Filter
    {
        return $this->createFilter(Filters::number()->isOdd());
    }
    
    /**
     * @inheritDoc
     */
    public function eq($value): Filter
    {
        return $this->createFilter(Filters::number()->eq($value));
    }
    
    /**
     * @inheritDoc
     */
    public function ne($value): Filter
    {
        return $this->createFilter(Filters::number()->ne($value));
    }
    
    /**
     * @inheritDoc
     */
    public function ge($value): Filter
    {
        return $this->createFilter(Filters::number()->ge($value));
    }
    
    /**
     * @inheritDoc
     */
    public function le($value): Filter
    {
        return $this->createFilter(Filters::number()->le($value));
    }
    
    /**
     * @inheritDoc
     */
    public function gt($value): Filter
    {
        return $this->createFilter(Filters::number()->gt($value));
    }
    
    /**
     * @inheritDoc
     */
    public function lt($value): Filter
    {
        return $this->createFilter(Filters::number()->lt($value));
    }
    
    /**
     * @inheritDoc
     */
    public function between($lower, $higher): Filter
    {
        return $this->createFilter(Filters::number()->between($lower, $higher));
    }
    
    /**
     * @inheritDoc
     */
    public function outside($lower, $higher): Filter
    {
        return $this->createFilter(Filters::number()->outside($lower, $higher));
    }
    
    /**
     * @inheritDoc
     */
    public function inside($lower, $higher): Filter
    {
        return $this->createFilter(Filters::number()->inside($lower, $higher));
    }
    
    /**
     * @inheritDoc
     */
    public function notInside($lower, $higher): Filter
    {
        return $this->createFilter(Filters::number()->notInside($lower, $higher));
    }
}