<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Operation\Internal\DispatchReady;
use FiiSoft\Jackdaw\Operation\Internal\ForkReady;
use FiiSoft\Jackdaw\Producer\ProducerReady;

abstract class LastOperation extends StreamPipe implements ResultApi, ForkReady, DispatchReady
{
    /**
     * Create new stream from the current one and set provided Producer as source of data for it.
     *
     * @param ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string $producer
     * @throws JackdawException this method throws an exception when consume() is called first
     */
    abstract public function wrap($producer): LastOperation;
    
    /**
     * Experimental. Causes stream to consume data provided by the passed producer. Can be called many times.
     * However, calling wrap() after calling consume() will result in an exception.
     *
     * @param ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string $producer
     */
    abstract public function consume($producer): void;
}