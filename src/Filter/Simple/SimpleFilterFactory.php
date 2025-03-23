<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Simple;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterFactory;

final class SimpleFilterFactory extends FilterFactory implements SimpleFilterPicker
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
        return $this->get(Equal::create($this->mode, $value));
    }
    
    /**
     * @inheritDoc
     */
    public function notEqual($value): Filter
    {
        return $this->get(NotEqual::create($this->mode, $value));
    }
    
    /**
     * @inheritDoc
     */
    public function same($value): Filter
    {
        return $this->get(Same::create($this->mode, $value));
    }
    
    /**
     * @inheritDoc
     */
    public function notSame($value): Filter
    {
        return $this->get(NotSame::create($this->mode, $value));
    }
}