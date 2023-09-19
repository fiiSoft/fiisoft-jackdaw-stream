<?php

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Stream;

interface Producer extends Destroyable, ProducerReady, MapperReady
{
    public function feed(Item $item): \Generator;
    
    public function stream(): Stream;
    
    public function isEmpty(): bool;
    
    public function isCountable(): bool;
    
    public function count(): int;
    
    public function getLast(): ?Item;
}