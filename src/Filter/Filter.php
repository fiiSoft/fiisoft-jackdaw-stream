<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Internal\StreamBuilder;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Transformer\TransformerReady;

interface Filter extends LogicFilter, StreamBuilder, FilterReady, MapperReady, DiscriminatorReady,
    ComparatorReady, TransformerReady
{
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function isAllowed($value, $key = null): bool;
    
    public function getMode(): ?int;
    
    public function inMode(?int $mode): self;
    
    public function checkValue(): self;
    
    public function checkKey(): self;
    
    public function checkBoth(): self;
    
    public function checkAny(): self;
    
    public function negate(): self;
    
    public function equals(Filter $other): bool;
}