<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Picker;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Time\TimeFilterPicker;

final class TimePicker extends BasePicker implements TimeFilterPicker
{
    public function isDateTime(): Filter
    {
        return $this->picker()->type()->isDateTime();
    }
    
    public function not(): TimeFilterPicker
    {
        return $this->negate();
    }
    
    /**
     * @inheritDoc
     */
    public function isDay(...$days): Filter
    {
        return $this->createFilter(Filters::time()->isDay(...$days));
    }
    
    /**
     * @inheritDoc
     */
    public function isNotDay(...$days): Filter
    {
        return $this->createFilter(Filters::time()->isNotDay(...$days));
    }
    
    /**
     * @inheritDoc
     */
    public function is($time): Filter
    {
        return $this->createFilter(Filters::time()->is($time));
    }
    
    /**
     * @inheritDoc
     */
    public function isNot($time): Filter
    {
        return $this->createFilter(Filters::time()->isNot($time));
    }
    
    /**
     * @inheritDoc
     */
    public function from($time): Filter
    {
        return $this->createFilter(Filters::time()->from($time));
    }
    
    /**
     * @inheritDoc
     */
    public function until($time): Filter
    {
        return $this->createFilter(Filters::time()->until($time));
    }
    
    /**
     * @inheritDoc
     */
    public function before($time): Filter
    {
        return $this->createFilter(Filters::time()->before($time));
    }
    
    /**
     * @inheritDoc
     */
    public function after($time): Filter
    {
        return $this->createFilter(Filters::time()->after($time));
    }
    
    /**
     * @inheritDoc
     */
    public function inside($earlier, $later): Filter
    {
        return $this->createFilter(Filters::time()->inside($earlier, $later));
    }
    
    /**
     * @inheritDoc
     */
    public function notInside($earlier, $later): Filter
    {
        return $this->createFilter(Filters::time()->notInside($earlier, $later));
    }
    
    /**
     * @inheritDoc
     */
    public function between($earlier, $later): Filter
    {
        return $this->createFilter(Filters::time()->between($earlier, $later));
    }
    
    /**
     * @inheritDoc
     */
    public function outside($earlier, $later): Filter
    {
        return $this->createFilter(Filters::time()->outside($earlier, $later));
    }
    
    /**
     * @inheritDoc
     */
    public function inSet(array $dates): Filter
    {
        return $this->createFilter(Filters::time()->inSet($dates));
    }
    
    /**
     * @inheritDoc
     */
    public function notInSet(array $dates): Filter
    {
        return $this->createFilter(Filters::time()->notInSet($dates));
    }
}