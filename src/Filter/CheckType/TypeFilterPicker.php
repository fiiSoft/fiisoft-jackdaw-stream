<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\Filter;

interface TypeFilterPicker
{
    public function not(): TypeFilterPicker;
    
    public function isNull(): Filter;
    
    public function notNull(): Filter;
    
    public function isEmpty(): Filter;
    
    public function notEmpty(): Filter;
    
    public function isInt(): Filter;
    
    public function isNumeric(): Filter;
    
    public function isString(): Filter;
    
    public function isBool(): Filter;
    
    public function isFloat(): Filter;
    
    public function isArray(): Filter;
    
    public function isCountable(): Filter;
    
    public function isDateTime(): Filter;
}