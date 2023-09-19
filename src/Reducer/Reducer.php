<?php

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Operation\Internal\Dispatcher\HandlerReady;

interface Reducer extends ResultProvider, ConsumerReady, MapperReady, HandlerReady
{
    /**
     * @param mixed $value
     * @return void
     */
    public function consume($value): void;
    
    /**
     * @return mixed|null
     */
    public function result();
    
    public function reset(): void;
}