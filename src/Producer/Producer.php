<?php

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Stream;

/**
 * @template K
 * @template V
 */
interface Producer extends Destroyable, ProducerReady, MapperReady, \IteratorAggregate
{
    public function stream(): Stream;
    
    /**
     * @return \Traversable<K, V>
     */
    public function getIterator(): \Traversable;
}