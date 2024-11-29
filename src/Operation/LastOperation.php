<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Internal\SignalHandler;
use FiiSoft\Jackdaw\Operation\Collecting\ForkReady;
use FiiSoft\Jackdaw\Producer\ProducerReady;

interface LastOperation extends ResultApi, SignalHandler, ForkReady
{
    /**
     * Create new stream from the current one and set provided Producer as source of data for it.
     *
     * @param ProducerReady|resource|callable|iterable<string|int, mixed>|string $producer
     */
    public function wrap($producer): LastOperation;
}