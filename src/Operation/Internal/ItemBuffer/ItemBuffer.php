<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\ItemBuffer;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;

interface ItemBuffer extends Destroyable
{
    public function hold(Item $item): void;
    
    /**
     * @return int max number of items possible to keep in buffer
     */
    public function getLength(): int;
    
    /**
     * @return int number of items hold in buffer
     */
    public function count(): int;
    
    /**
     * Remove all collected items.
     */
    public function clear(): void;
    
    /**
     * @return Producer allows to iterate over collected items
     */
    public function createProducer(): Producer;
    
    /**
     * Returns collected data as array, with original keys or reindexed.
     * When param $skip is greather than 0, it skips $skip elements at the beginning.
     *
     * @return array<string|int, mixed>
     */
    public function fetchData(bool $reindex = false, int $skip = 0): array;
}