<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Segregate;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class BucketListIterator extends BaseProducer
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
    
    public function getIterator(): \Generator
    {
        $index = -1;
        
        foreach ($this->buckets as $bucket) {
            yield ++$index => $bucket->data;
        }
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