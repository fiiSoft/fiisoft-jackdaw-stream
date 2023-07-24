<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector;

use FiiSoft\Jackdaw\Stream;

interface IterableCollector extends Collector, \Traversable, \Countable
{
    public function clear(): void;
    
    public function getData(): array;
    
    public function stream(): Stream;
    
    public function toString(string $separator = ','): string;
    
    public function toJson(int $flags = 0): string;
}