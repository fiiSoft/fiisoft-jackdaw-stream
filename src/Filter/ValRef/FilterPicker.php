<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef;

use FiiSoft\Jackdaw\Filter\CheckType\TypeFilterPicker;
use FiiSoft\Jackdaw\Filter\Number\NumberFilterPicker;
use FiiSoft\Jackdaw\Filter\Simple\SimpleFilterPicker;
use FiiSoft\Jackdaw\Filter\Size\Count\CountFilterPicker;
use FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterPicker;
use FiiSoft\Jackdaw\Filter\String\StringFilterPicker;
use FiiSoft\Jackdaw\Filter\Time\TimeFilterPicker;
use FiiSoft\Jackdaw\Filter\ValRef\Picker\CountPicker;
use FiiSoft\Jackdaw\Filter\ValRef\Picker\LengthPicker;
use FiiSoft\Jackdaw\Filter\ValRef\Picker\NumberPicker;
use FiiSoft\Jackdaw\Filter\ValRef\Picker\SimplePicker;
use FiiSoft\Jackdaw\Filter\ValRef\Picker\StringPicker;
use FiiSoft\Jackdaw\Filter\ValRef\Picker\TimePicker;
use FiiSoft\Jackdaw\Filter\ValRef\Picker\TypePicker;

abstract class FilterPicker
{
    private ?FilterAdapterFactory $factory = null;
    
    private bool $isNot;
    
    public function __construct(bool $isNot = false)
    {
        $this->isNot = $isNot;
    }
    
    final public function string(): StringFilterPicker
    {
        return new StringPicker($this->factory(), $this->isNot);
    }
    
    final public function length(): LengthFilterPicker
    {
        return new LengthPicker($this->factory(), $this->isNot);
    }
    
    final public function size(): CountFilterPicker
    {
        return new CountPicker($this->factory(), $this->isNot);
    }
    
    final public function time(): TimeFilterPicker
    {
        return new TimePicker($this->factory(), $this->isNot);
    }
    
    final public function number(): NumberFilterPicker
    {
        return new NumberPicker($this->factory(), $this->isNot);
    }
    
    final public function type(): TypeFilterPicker
    {
        return new TypePicker($this->factory(), $this->isNot);
    }
    
    final public function is(): SimpleFilterPicker
    {
        return new SimplePicker($this->factory(), $this->isNot);
    }
    
    final public function not(): self
    {
        $copy = clone $this;
        $copy->isNot = !$this->isNot;
        
        return $copy;
    }
    
    private function factory(): FilterAdapterFactory
    {
        if ($this->factory === null) {
            $this->factory = $this->createFactory();
        }
        
        return $this->factory;
    }
    
    abstract protected function createFactory(): FilterAdapterFactory;
}