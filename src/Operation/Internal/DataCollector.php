<?php

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Producer\Producer;

interface DataCollector
{
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool;
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool;
    
    /**
     * @param Item[] $items
     */
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool;
}