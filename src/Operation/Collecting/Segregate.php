<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Segregate\Bucket;
use FiiSoft\Jackdaw\Operation\Collecting\Segregate\BucketListIterator;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Limitable;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;

final class Segregate extends BaseOperation implements Limitable, Reindexable
{
    private ItemComparator $comparator;
    private Comparison $comparison;

    /** @var Bucket[] */
    private array $buckets = [];
    
    private int $maxBuckets, $countBuckets = 0, $lastBucket = 0, $limit;
    private bool $reindex;
    
    /**
     * @param int|null $buckets null means collect all elements
     * @param ComparatorReady|callable|null $comparison
     * @param int|null $limit max number of collected elements in each bucket; null means no limits
     */
    public function __construct(?int $buckets = null, bool $reindex = false, $comparison = null, ?int $limit = null)
    {
        if ($buckets === null) {
            $buckets = \PHP_INT_MAX;
        } elseif ($buckets < 1) {
            throw InvalidParamException::describe('buckets', $buckets);
        }
        
        if ($limit === null) {
            $limit = \PHP_INT_MAX;
        } elseif ($limit < 1) {
            throw InvalidParamException::describe('limit', $limit);
        }
        
        $this->reindex = $reindex;
        $this->maxBuckets = $buckets;
        $this->limit = $limit;
        
        $this->comparison = Comparison::prepare($comparison);
    }
    
    public function prepare(): void
    {
        parent::prepare();
        
        $this->comparator = ItemComparatorFactory::getForComparison($this->comparison);
        $this->buckets[0] = new Bucket($this->reindex, $this->limit);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->countBuckets === 0) {
            ++$this->countBuckets;
            $this->buckets[0]->add($signal->item);
            
            return;
        }
        
        $left = 0;
        $index = $right = $this->lastBucket;
        
        while (true) {
            $current = $this->buckets[$index];
            $compare = $this->comparator->compare($signal->item, $current->item);
            
            if ($compare > 0) {
                if ($index < $right) {
                    $left = $index + 1;
                    $index = $left + (int) (($right - $index) / 2);
                    
                    continue;
                }
                
                if ($this->countBuckets < $this->maxBuckets) {
                    
                    if ($index < $this->lastBucket) {
                        $this->shiftBuckets($index + 1, $this->lastBucket);
                    }
                    
                    $this->buckets[$index + 1] = $current->create($signal->item);
                    
                    ++$this->countBuckets;
                    ++$this->lastBucket;
                    
                } elseif ($this->maxBuckets > 1) {
                    if ($index === $this->lastBucket) {
                        return;
                    }
                    
                    $this->buckets[$this->lastBucket]->clear();
                    $this->shiftBuckets($index + 1, $this->lastBucket - 1);
                    
                    $this->buckets[$index + 1] = $current->create($signal->item);
                }
                
                return;
            }
            
            if ($compare === 0) {
                $current->add($signal->item);
                return;
            }
            
            if ($index > $left) {
                $right = $index - 1;
                $index = $left + (int) (($index - $left) / 2);
                
                continue;
            }
            
            if ($this->countBuckets < $this->maxBuckets) {
                $this->shiftBuckets($index, $this->lastBucket);
                $this->buckets[$index] = $current->create($signal->item);
                
                ++$this->countBuckets;
                ++$this->lastBucket;
                
            } elseif ($this->maxBuckets > 1) {
                $this->buckets[$this->lastBucket]->clear();
                $this->shiftBuckets($index, $this->lastBucket - 1);
                $this->buckets[$index] = $current->create($signal->item);
            } else {
                $current->replace($signal->item);
            }
            
            return;
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $item = new Item();
        
        foreach ($stream as $item->key => $item->value) {
            if ($this->countBuckets === 0) {
                ++$this->countBuckets;
                $this->buckets[0]->add($item);
                
                continue;
            }
            
            $left = 0;
            $index = $right = $this->lastBucket;
            
            while (true) {
                $current = $this->buckets[$index];
                $compare = $this->comparator->compare($item, $current->item);
                
                if ($compare > 0) {
                    if ($index < $right) {
                        $left = $index + 1;
                        $index = $left + (int) (($right - $index) / 2);
                        
                        continue;
                    }
                    
                    if ($this->countBuckets < $this->maxBuckets) {
                        
                        if ($index < $this->lastBucket) {
                            $this->shiftBuckets($index + 1, $this->lastBucket);
                        }
                        
                        $this->buckets[$index + 1] = $current->create($item);
                        
                        ++$this->countBuckets;
                        ++$this->lastBucket;
                        
                    } elseif ($this->maxBuckets > 1) {
                        if ($index === $this->lastBucket) {
                            continue 2;
                        }
                        
                        $this->buckets[$this->lastBucket]->clear();
                        $this->shiftBuckets($index + 1, $this->lastBucket - 1);
                        
                        $this->buckets[$index + 1] = $current->create($item);
                    }
                    
                    continue 2;
                }
                
                if ($compare === 0) {
                    $current->add($item);
                    
                    continue 2;
                }
                
                if ($index > $left) {
                    $right = $index - 1;
                    $index = $left + (int) (($index - $left) / 2);
                    
                    continue;
                }
                
                if ($this->countBuckets < $this->maxBuckets) {
                    $this->shiftBuckets($index, $this->lastBucket);
                    $this->buckets[$index] = $current->create($item);
                    
                    ++$this->countBuckets;
                    ++$this->lastBucket;
                    
                } elseif ($this->maxBuckets > 1) {
                    $this->buckets[$this->lastBucket]->clear();
                    $this->shiftBuckets($index, $this->lastBucket - 1);
                    $this->buckets[$index] = $current->create($item);
                } else {
                    $current->replace($item);
                }
                
                continue 2;
            }
        }
        
        if ($this->countBuckets === 0) {
            return [];
        }
        
        yield from new BucketListIterator($this->buckets);
        
        $this->destroyBuckets();
    }
    
    private function shiftBuckets(int $from, int $last): void
    {
        for ($i = $last; $i >= $from; --$i) {
            $this->buckets[$i + 1] = $this->buckets[$i];
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($this->countBuckets === 0) {
            return false;
        }
        
        $signal->restartWith(new BucketListIterator($this->buckets), $this->next);
        $this->buckets = [];
        
        return true;
    }
    
    public function createWithLimit(int $limit): Limitable
    {
        return new self($limit > 0 ? $limit : 1, $this->reindex, $this->comparison);
    }
    
    public function applyLimit(int $limit): bool
    {
        if ($limit > 0) {
            $this->maxBuckets = \min($limit, $this->maxBuckets);
            return true;
        }
        
        return false;
    }
    
    public function limit(): int
    {
        return $this->maxBuckets;
    }
    
    public function isReindexed(): bool
    {
        return $this->reindex;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->destroyBuckets();
            parent::destroy();
        }
    }
    
    private function destroyBuckets(): void
    {
        foreach ($this->buckets as $bucket) {
            $bucket->destroy();
        }
        
        $this->buckets = [];
    }
}