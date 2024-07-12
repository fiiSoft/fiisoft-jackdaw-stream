<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class CombinedGeneral extends BaseProducer
{
    private Producer $keys, $values;
    
    /**
     * @param ProducerReady|resource|callable|iterable<int, mixed>|string $keys
     * @param ProducerReady|resource|callable|iterable<int, mixed>|string $values
     */
    public function __construct($keys, $values)
    {
        $this->keys = Producers::getAdapter($keys);
        $this->values = Producers::getAdapter($values);
    }
    
    public function getIterator(): \Generator
    {
        $keyFetcher = $this->buildIterator($this->keys);
        $valueFetcher = $this->buildIterator($this->values);
        
        while ($keyFetcher->valid() && $valueFetcher->valid()) {
            yield $keyFetcher->current() => $valueFetcher->current();
            
            $keyFetcher->next();
            $valueFetcher->next();
        }
    }
    
    private function buildIterator(Producer $producer): \Iterator
    {
        $iterator = $producer->getIterator();
        
        if ($iterator instanceof \Iterator) {
            return $iterator;
        }
        
        //@codeCoverageIgnoreStart
        return (static function () use ($iterator): \Generator {
            yield from $iterator;
        })();
        //@codeCoverageIgnoreEnd
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->keys->destroy();
            $this->values->destroy();
            
            parent::destroy();
        }
    }
}