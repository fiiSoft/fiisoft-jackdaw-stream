<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

interface LogicFilter
{
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function and($filter): Filter;
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function andNot($filter): Filter;
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function or($filter): Filter;
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function orNot($filter): Filter;
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function xor($filter): Filter;
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function xnor($filter): Filter;
}