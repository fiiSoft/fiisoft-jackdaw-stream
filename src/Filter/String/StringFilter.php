<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\Filter;

interface StringFilter extends Filter
{
    public function inMode(?int $mode): self;

    public function checkValue(): self;
    
    public function checkKey(): self;
    
    public function checkBoth(): self;
    
    public function checkAny(): self;
    
    public function negate(): self;
    
    public function ignoreCase(): StringFilter;
    
    public function caseSensitive(): StringFilter;
    
    public function isCaseInsensitive(): bool;
}