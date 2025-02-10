<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Internal\SignalHandler;
use FiiSoft\Jackdaw\Operation\Collecting\ForkReady;
use FiiSoft\Jackdaw\Producer\ProducerReady;

interface LastOperation extends ResultApi, SignalHandler, ForkReady
{
    /**
     * Create new stream from the current one and set provided Producer as source of data for it.
     *
     * @param ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string $producer
     * @throws JackdawException this method throws an exception when consume() is called first
     */
    public function wrap($producer): LastOperation;
    
    /**
     * @param ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string $producer
     */
    public function consume($producer): void;
}