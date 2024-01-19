<?php

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Stream;

interface Producer extends Destroyable, ProducerReady, MapperReady, \IteratorAggregate
{
    public function stream(): Stream;
}