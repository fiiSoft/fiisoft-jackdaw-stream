<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size\Length;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterFactory;
use FiiSoft\Jackdaw\Filter\Filters;

final class LengthFilterFactory extends FilterFactory
{
    public static function instance(?int $mode = null): self
    {
        return new self($mode);
    }
    
    public function isString(): Filter
    {
        return $this->get(Filters::isString($this->mode));
    }
    
    /**
     * $x == $length
     */
    public function eq(int $length): Filter
    {
        return $this->get(LengthFilter::create($this->mode, Filters::number()->eq($length)));
    }
    
    /**
     * $x != $length
     */
    public function ne(int $length): Filter
    {
        return $this->get(LengthFilter::create($this->mode, Filters::number()->ne($length)));
    }
    
    /**
     * $x < $length
     */
    public function lt(int $length): Filter
    {
        return $this->get(LengthFilter::create($this->mode, Filters::number()->lt($length)));
    }
    
    /**
     * $x <= $length
     */
    public function le(int $length): Filter
    {
        return $this->get(LengthFilter::create($this->mode, Filters::number()->le($length)));
    }
    
    /**
     * $x > $length
     */
    public function gt(int $length): Filter
    {
        return $this->get(LengthFilter::create($this->mode, Filters::number()->gt($length)));
    }
    
    /**
     * $x >= $length
     */
    public function ge(int $length): Filter
    {
        return $this->get(LengthFilter::create($this->mode, Filters::number()->ge($length)));
    }
    
    /**
     * $x >= $min && $x <= $max
     */
    public function between(int $min, int $max): Filter
    {
        return $this->get(LengthFilter::create($this->mode, Filters::number()->between($min, $max)));
    }
    
    /**
     * $x < $min || $x > $max
     */
    public function outside(int $min, int $max): Filter
    {
        return $this->get(LengthFilter::create($this->mode, Filters::number()->outside($min, $max)));
    }
    
    /**
     * $x > $min && $x < $max
     */
    public function inside(int $min, int $max): Filter
    {
        return $this->get(LengthFilter::create($this->mode, Filters::number()->inside($min, $max)));
    }
    
    /**
     * $x <= $min || $x => $max
     */
    public function notInside(int $min, int $max): Filter
    {
        return $this->get(LengthFilter::create($this->mode, Filters::number()->notInside($min, $max)));
    }
}