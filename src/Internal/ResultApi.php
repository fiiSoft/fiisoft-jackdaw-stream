<?php

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Operation\Internal\Dispatcher\HandlerReady;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\Transformer\Transformer;

interface ResultApi extends HandlerReady, ResultCaster, Destroyable, \Countable
{
    public function found(): bool;
    
    public function notFound(): bool;
    
    /**
     * This method should be called value(), because it returns value!
     *
     * @return mixed|null
     */
    public function get();
    
    /**
     * Register single transformer to perform final opertations on result (when result is available).
     *
     * @param Transformer|Mapper|Reducer|Filter|callable|null $transformer
     */
    public function transform($transformer): ResultApi;
    
    /**
     * @param callable|mixed|null $orElse callable is lazy-evaluated result when nothing was found
     * @return mixed|null
     */
    public function getOrElse($orElse);
    
    /**
     * @return int|string
     */
    public function key();
    
    /**
     * @return array with two values: first is key, second is value, both indexed numerically
     */
    public function tuple(): array;
    
    /**
     * @param Consumer|Reducer|callable|resource $consumer
     */
    public function call($consumer): void;
    
    /**
     * Use values collected in result as stream.
     * Because result holds computed values, every call to this method creates new stream.
     */
    public function stream(): Stream;
}