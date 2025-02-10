<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;

interface NumberFilterPicker
{
    public function isNumeric(): Filter;
    
    public function isFloat(): Filter;
    
    public function isInt(): Filter;
    
    public function not(): NumberFilterPicker;
    
    /**
     * ($x & 1) === 0
     */
    public function isEven(): Filter;
    
    /**
     * ($x & 1) === 1
     */
    public function isOdd(): Filter;
    
    /**
     * $x != $value
     *
     * @param float|int $value
     */
    public function eq($value): Filter;
    
    /**
     * $x != $value
     *
     * @param float|int $value
     */
    public function ne($value): Filter;
    
    /**
     * $x => $value
     *
     * @param float|int $value
     */
    public function ge($value): Filter;
    
    /**
     * $x <= $value
     *
     * @param float|int $value
     */
    public function le($value): Filter;
    
    /**
     * $x > $value
     *
     * @param float|int $value
     */
    public function gt($value): Filter;
    
    /**
     * $x < $value
     *
     * @param float|int $value
     */
    public function lt($value): Filter;
    
    /**
     * $x >= $min && $x <= $max
     *
     * @param float|int $lower
     * @param float|int $higher
     */
    public function between($lower, $higher): Filter;
    
    /**
     * $x < $min || $x > $max
     *
     * @param float|int $lower
     * @param float|int $higher
     */
    public function outside($lower, $higher): Filter;
    
    /**
     * $x > $min && $x < $max
     *
     * @param float|int $lower
     * @param float|int $higher
     */
    public function inside($lower, $higher): Filter;
    
    /**
     * $x <= $min || $x => $max
     *
     * @param float|int $lower
     * @param float|int $higher
     */
    public function notInside($lower, $higher): Filter;
}