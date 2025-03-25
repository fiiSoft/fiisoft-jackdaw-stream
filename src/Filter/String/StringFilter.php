<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;

interface StringFilter extends Filter
{
    public function inMode(?int $mode): self;

    public function checkValue(): self;

    public function checkKey(): self;

    public function checkBoth(): self;

    public function checkAny(): self;

    public function negate(): self;
    
    public function ignoreCase(): self;
    
    public function caseSensitive(): self;
    
    public function isCaseInsensitive(): bool;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function and($filter): self;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function andNot($filter): self;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function or($filter): self;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function orNot($filter): self;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function xor($filter): self;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function xnor($filter): self;
}