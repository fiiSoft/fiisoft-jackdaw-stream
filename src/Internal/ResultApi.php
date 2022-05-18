<?php

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\StreamMaker;
use FiiSoft\Jackdaw\Transformer\Transformer;

interface ResultApi extends ResultCaster, \Countable
{
    public function run(): void;
    
    public function found(): bool;
    
    public function notFound(): bool;
    
    /**
     * @return mixed|null
     */
    public function get();
    
    /**
     * Register single transformer to perform final opertations on result (when result is available).
     *
     * @param Transformer|Mapper|Reducer|callable|null $transformer
     * @return $this
     */
    public function transform($transformer): self;
    
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
     * @param Consumer|callable|resource $consumer
     * @return void
     */
    public function call($consumer): void;
    
    /**
     * Use values collected in result as stream.
     * Because result holds computed values, created Stream is reusable.
     *
     * @return StreamMaker
     */
    public function stream(): StreamMaker;
}