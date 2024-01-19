<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Operation\Collecting\Segregate\Bucket;
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
        $index = 0;
        
        foreach ($this->buckets as $bucket) {
            yield $index++ => $bucket->data;
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