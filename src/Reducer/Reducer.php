<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Operation\Collecting\ForkReady;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\HandlerReady;
use FiiSoft\Jackdaw\Transformer\TransformerReady;

interface Reducer extends ResultProvider, ConsumerReady, MapperReady, HandlerReady, ForkReady, TransformerReady
{
    /**
     * @param mixed $value
     */
    public function consume($value): void;
    
    /**
     * @return mixed|null
     */
    public function result();
    
    public function reset(): void;
}