<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector;

use FiiSoft\Jackdaw\Stream;

interface IterableCollector extends Collector, \Traversable, \Countable
{
    public function clear(): void;
    
    public function stream(): Stream;
    
    public function toArray(): array;
    
    public function toString(string $separator = ','): string;
    
    public function toJson(?int $flags = null): string;
}