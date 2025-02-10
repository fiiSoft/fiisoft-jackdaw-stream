<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size\Count;

use FiiSoft\Jackdaw\Filter\Filter;

interface CountFilterPicker
{
    public function isCountable(): Filter;
    
    public function not(): CountFilterPicker;
    
    /**
     * $x == $size
     */
    public function eq(int $size): Filter;
    
    /**
     * $x != $size
     */
    public function ne(int $size): Filter;
    
    /**
     * $x <= $size
     */
    public function le(int $size): Filter;
    
    /**
     * $x >= $size
     */
    public function ge(int $size): Filter;
    
    /**
     * $x < $size
     */
    public function lt(int $size): Filter;
    
    /**
     * $x > $size
     */
    public function gt(int $size): Filter;
    
    /**
     * $x > $min && $x < $max
     */
    public function inside(int $min, int $max): Filter;
    
    /**
     * $x <= $min || $x => $max
     */
    public function notInside(int $min, int $max): Filter;
    
    /**
     * $x >= $min && $x <= $max
     */
    public function between(int $min, int $max): Filter;
    
    /**
     * $x < $min || $x > $max
     */
    public function outside(int $min, int $max): Filter;
}