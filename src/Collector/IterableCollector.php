<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector;

use FiiSoft\Jackdaw\Operation\Internal\ForkReady;
use FiiSoft\Jackdaw\Stream;

/**
 * @extends \Traversable<string|int, mixed>
 */
interface IterableCollector extends Collector, ForkReady, \Traversable, \Countable
{
    public function clear(): void;
    
    public function stream(): Stream;
    
    /**
     * @return array<string|int, mixed>
     */
    public function toArray(): array;
    
    public function toString(string $separator = ','): string;
    
    public function toJson(?int $flags = null): string;
}