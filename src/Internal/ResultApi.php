<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\Transformer\TransformerReady;

/**
 * @extends \IteratorAggregate<string|int, mixed>
 */
interface ResultApi extends ResultCaster, Destroyable, \Countable, \IteratorAggregate
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
     * @param TransformerReady|callable|null $transformer
     * @return $this fluent interface
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
     * @return array{}|array{string|int, mixed} with two values: first is key, second is value, both indexed numerically
     */
    public function tuple(): array;
    
    /**
     * @param ConsumerReady|callable|resource $consumer
     */
    public function call($consumer): void;
    
    /**
     * Use values collected in result as stream.
     * Because result holds computed values, every call to this method creates new stream.
     */
    public function stream(): Stream;
}