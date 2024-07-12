<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Internal\StreamBuilder;
use FiiSoft\Jackdaw\Mapper\MapperReady;

interface Filter extends MapperReady, ConditionReady, DiscriminatorReady, ComparatorReady, StreamBuilder
{
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function isAllowed($value, $key = null): bool;
    
    public function inMode(?int $mode): Filter;
    
    public function negate(): Filter;
    
    public function getMode(): ?int;
    
    public function checkValue(): Filter;
    
    public function checkKey(): Filter;
    
    public function checkBoth(): Filter;
    
    public function checkAny(): Filter;
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public function and($filter): Filter;

    /**
     * @param Filter|callable|mixed $filter
     */
    public function andNot($filter): Filter;

    /**
     * @param Filter|callable|mixed $filter
     */
    public function or($filter): Filter;

    /**
     * @param Filter|callable|mixed $filter
     */
    public function orNot($filter): Filter;

    /**
     * @param Filter|callable|mixed $filter
     */
    public function xor($filter): Filter;

    /**
     * @param Filter|callable|mixed $filter
     */
    public function xnor($filter): Filter;
}