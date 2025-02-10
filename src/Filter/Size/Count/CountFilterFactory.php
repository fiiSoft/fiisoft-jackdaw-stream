<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size\Count;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterFactory;
use FiiSoft\Jackdaw\Filter\Filters;

final class CountFilterFactory extends FilterFactory implements CountFilterPicker
{
    public static function instance(?int $mode = null): self
    {
        return new self($mode);
    }
    
    public function isCountable(): Filter
    {
        return $this->get(Filters::isCountable($this->mode));
    }
    
    public function not(): CountFilterPicker
    {
        return $this->negate();
    }
    
    /**
     * $x == $size
     */
    public function eq(int $size): Filter
    {
        return $this->get(CountFilter::create($this->mode, Filters::number()->eq($size)));
    }
    
    /**
     * $x != $size
     */
    public function ne(int $size): Filter
    {
        return $this->get(CountFilter::create($this->mode, Filters::number()->ne($size)));
    }
    
    /**
     * $x < $size
     */
    public function lt(int $size): Filter
    {
        return $this->get(CountFilter::create($this->mode, Filters::number()->lt($size)));
    }
    
    /**
     * $x <= $size
     */
    public function le(int $size): Filter
    {
        return $this->get(CountFilter::create($this->mode, Filters::number()->le($size)));
    }
    
    /**
     * $x > $size
     */
    public function gt(int $size): Filter
    {
        return $this->get(CountFilter::create($this->mode, Filters::number()->gt($size)));
    }
    
    /**
     * $x >= $size
     */
    public function ge(int $size): Filter
    {
        return $this->get(CountFilter::create($this->mode, Filters::number()->ge($size)));
    }
    
    /**
     * $x >= $min && $x <= $max
     */
    public function between(int $min, int $max): Filter
    {
        return $this->get(CountFilter::create($this->mode, Filters::number()->between($min, $max)));
    }
    
    /**
     * $x < $min || $x > $max
     */
    public function outside(int $min, int $max): Filter
    {
        return $this->get(CountFilter::create($this->mode, Filters::number()->outside($min, $max)));
    }
    
    /**
     * $x > $min && $x < $max
     */
    public function inside(int $min, int $max): Filter
    {
        return $this->get(CountFilter::create($this->mode, Filters::number()->inside($min, $max)));
    }
    
    /**
     * $x <= $min || $x => $max
     */
    public function notInside(int $min, int $max): Filter
    {
        return $this->get(CountFilter::create($this->mode, Filters::number()->notInside($min, $max)));
    }
}