<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterFactory;
use FiiSoft\Jackdaw\Filter\Filters;

final class NumberFilterFactory extends FilterFactory implements NumberFilterPicker
{
    public static function instance(?int $mode = null): self
    {
        return new self($mode);
    }
    
    public function isInt(): Filter
    {
        return $this->get(Filters::isInt($this->mode));
    }
    
    public function isFloat(): Filter
    {
        return $this->get(Filters::isFloat($this->mode));
    }
    
    public function isNumeric(): Filter
    {
        return $this->get(Filters::isNumeric($this->mode));
    }
    
    public function not(): NumberFilterPicker
    {
        return $this->negate();
    }
    
    /**
     * $x != $value
     *
     * @param float|int $value
     */
    public function eq($value): Filter
    {
        return $this->get(Equal::create($this->mode, $value));
    }
    
    /**
     * $x != $value
     *
     * @param float|int $value
     */
    public function ne($value): Filter
    {
        return $this->get(NotEqual::create($this->mode, $value));
    }
    
    /**
     * $x < $value
     *
     * @param float|int $value
     */
    public function lt($value): Filter
    {
        return $this->get(LessThan::create($this->mode, $value));
    }
    
    /**
     * $x <= $value
     *
     * @param float|int $value
     */
    public function le($value): Filter
    {
        return $this->get(LessOrEqual::create($this->mode, $value));
    }
    
    /**
     * $x > $value
     *
     * @param float|int $value
     */
    public function gt($value): Filter
    {
        return $this->get(GreaterThan::create($this->mode, $value));
    }
    
    /**
     * $x => $value
     *
     * @param float|int $value
     */
    public function ge($value): Filter
    {
        return $this->get(GreaterOrEqual::create($this->mode, $value));
    }
    
    /**
     * ($x & 1) === 0
     */
    public function isEven(): Filter
    {
        return $this->get(IsEven::create($this->mode));
    }
    
    /**
     * ($x & 1) === 1
     */
    public function isOdd(): Filter
    {
        return $this->get(IsOdd::create($this->mode));
    }
    
    /**
     * $x >= $min && $x <= $max
     *
     * @param float|int $lower
     * @param float|int $higher
     */
    public function between($lower, $higher): Filter
    {
        return $this->get(Between::create($this->mode, $lower, $higher));
    }
    
    /**
     * $x < $min || $x > $max
     *
     * @param float|int $lower
     * @param float|int $higher
     */
    public function outside($lower, $higher): Filter
    {
        return $this->get(Outside::create($this->mode, $lower, $higher));
    }
    
    /**
     * $x > $min && $x < $max
     *
     * @param float|int $lower
     * @param float|int $higher
     */
    public function inside($lower, $higher): Filter
    {
        return $this->get(Inside::create($this->mode, $lower, $higher));
    }
    
    /**
     * $x <= $min || $x => $max
     *
     * @param float|int $lower
     * @param float|int $higher
     */
    public function notInside($lower, $higher): Filter
    {
        return $this->get(NotInside::create($this->mode, $lower, $higher));
    }
}