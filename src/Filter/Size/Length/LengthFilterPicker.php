<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size\Length;

use FiiSoft\Jackdaw\Filter\Filter;

interface LengthFilterPicker
{
    public function isString(): Filter;
    
    public function not(): LengthFilterPicker;
    
    /**
     * $x == $length
     */
    public function eq(int $length): Filter;
    
    /**
     * $x != $length
     */
    public function ne(int $length): Filter;
    
    /**
     * $x <= $length
     */
    public function le(int $length): Filter;
    
    /**
     * $x >= $length
     */
    public function ge(int $length): Filter;
    
    /**
     * $x > $length
     */
    public function gt(int $length): Filter;
    
    /**
     * $x < $length
     */
    public function lt(int $length): Filter;
    
    /**
     * $x > $min && $x < $max
     */
    public function inside(int $min, int $max): Filter;
    
    /**
     * $x < $min || $x > $max
     */
    public function outside(int $min, int $max): Filter;
    
    /**
     * $x >= $min && $x <= $max
     */
    public function between(int $min, int $max): Filter;
    
    /**
     * $x <= $min || $x => $max
     */
    public function notInside(int $min, int $max): Filter;
}