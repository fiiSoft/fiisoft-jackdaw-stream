<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Cache;

use FiiSoft\Jackdaw\Internal\Item;

/**
 * @implements \IteratorAggregate<string|int, mixed>
 */
final class CachedData implements \IteratorAggregate
{
    public bool $isFilled = false;
    
    /** @var Item[] */
    public array $items = [];
    
    /** @var array<string|int, mixed> */
    public array $data = [];
    
    public function getIterator(): \Generator
    {
        if (empty($this->items)) {
            yield from $this->data;
        } else {
            foreach ($this->items as $x) {
                yield $x->key => $x->value;
            }
        }
    }
}