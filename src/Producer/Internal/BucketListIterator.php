<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Segregate\Bucket;
use FiiSoft\Jackdaw\Producer\Tech\CountableProducer;

final class BucketListIterator extends CountableProducer
{
    /** @var Bucket[] */
    private array $buckets;
    
    /**
     * @param Bucket[] $buckets
     */
    public function __construct(array $buckets)
    {
        $this->buckets = $buckets;
    }
    
    public function feed(Item $item): \Generator
    {
        $index = 0;
        
        foreach ($this->buckets as $bucket) {
            $item->key = $index++;
            $item->value = $bucket->data;
            yield;
        }
    }
    
    public function count(): int
    {
        return \count($this->buckets);
    }
    
    public function getLast(): ?Item
    {
        if (empty($this->buckets)) {
            return null;
        }
        
        $key = \array_key_last($this->buckets);
        
        return new Item($key, $this->buckets[$key]->data);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            foreach ($this->buckets as $bucket) {
                $bucket->destroy();
            }
            
            $this->buckets = [];
            
            parent::destroy();
        }
    }
}