<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Picker;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Simple\SimpleFilterPicker;

final class SimplePicker extends BasePicker implements SimpleFilterPicker
{
    public function not(): SimpleFilterPicker
    {
        return $this->negate();
    }
    
    /**
     * @inheritDoc
     */
    public function equal($value): Filter
    {
        return $this->createFilter(Filters::equal($value));
    }
    
    /**
     * @inheritDoc
     */
    public function notEqual($value): Filter
    {
        return $this->createFilter(Filters::notEqual($value));
    }
    
    /**
     * @inheritDoc
     */
    public function same($value): Filter
    {
        return $this->createFilter(Filters::same($value));
    }
    
    /**
     * @inheritDoc
     */
    public function notSame($value): Filter
    {
        return $this->createFilter(Filters::notSame($value));
    }
}