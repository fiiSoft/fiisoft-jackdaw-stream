<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Simple;

use FiiSoft\Jackdaw\Filter\Filter;

interface SimpleFilterPicker
{
    public function not(): SimpleFilterPicker;
    
    /**
     * @param mixed $value
     */
    public function equal($value): Filter;
    
    /**
     * @param mixed $value
     */
    public function notEqual($value): Filter;
    
    /**
     * @param mixed $value
     */
    public function same($value): Filter;
    
    /**
     * @param mixed $value
     */
    public function notSame($value): Filter;
}