<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Picker\Custom;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\IdleFilter;
use FiiSoft\Jackdaw\Filter\Number\NumberFilterPicker;
use FiiSoft\Jackdaw\Filter\ValRef\Adapter\IntValue\IntValueFilter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\ValueRef\IntValue;

final class IntValNumberFilterPicker implements NumberFilterPicker
{
    private IntValue $intValue;
    
    private bool $isNot;
    
    public function __construct(IntValue $intValue, bool $isNot = false)
    {
        $this->intValue = $intValue;
        $this->isNot = $isNot;
    }
    
    public function isNumeric(): Filter
    {
        return IdleFilter::true(Check::VALUE);
    }
    
    public function isFloat(): Filter
    {
        return IdleFilter::false(Check::VALUE);
    }
    
    public function isInt(): Filter
    {
        return IdleFilter::true(Check::VALUE);
    }
    
    public function not(): NumberFilterPicker
    {
        return new self($this->intValue, !$this->isNot);
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
    
    private function createFilter(Filter $filter): Filter
    {
        $adapter = new IntValueFilter($this->intValue, $filter);
        
        return $this->isNot ? $adapter->negate() : $adapter;
    }
}