<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\Transformer\TransformerReady;

/**
 * @extends \IteratorAggregate<string|int, mixed>
 */
interface ResultApi extends ProducerReady, MapperReady, Destroyable, \Countable, \IteratorAggregate
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
    
    public function toString(string $separator = ','): string;
    
    public function toJson(?int $flags = null, bool $preserveKeys = false): string;
    
    /**
     * It works in the same way as toJson($flags, true).
     */
    public function toJsonAssoc(?int $flags = null): string;
    
    /**
     * @return array<string|int, mixed>
     */
    public function toArray(bool $preserveKeys = false): array;
    
    /**
     * It works in the same way as toArray(true).
     *
     * @return array<string|int, mixed>
     */
    public function toArrayAssoc(): array;
}