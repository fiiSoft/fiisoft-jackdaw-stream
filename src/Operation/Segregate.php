<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\DataCollector;
use FiiSoft\Jackdaw\Operation\Internal\Limitable;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;
use FiiSoft\Jackdaw\Operation\Segregate\Bucket;
use FiiSoft\Jackdaw\Producer\Internal\BucketListIterator;
use FiiSoft\Jackdaw\Producer\Producer;

final class Segregate extends BaseOperation implements Limitable, Reindexable, DataCollector
{
    private ItemComparator $comparator;

    /** @var Bucket[] */
    private array $buckets = [];
    
    private int $limit, $count = 0, $last = 0;
    private bool $reindex;
    
    /**
     * @param int|null $buckets null means collect all elements
     * @param Comparator|callable|null $comparator
     */
    public function __construct(
        ?int $buckets = null,
        $comparator = null,
        int $mode = Check::VALUE,
        bool $reindex = false
    ) {
        if ($buckets === null) {
            $buckets = \PHP_INT_MAX;
        } elseif ($buckets < 1) {
            throw new \InvalidArgumentException('Invalid param buckets');
        }
        
        $this->comparator = ItemComparatorFactory::getFor($mode, false, $comparator);
        $this->limit = $buckets;
        
        $this->buckets[0] = new Bucket($reindex);
        $this->reindex = $reindex;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->count === 0) {
            $this->buckets[0]->add($signal->item);
            ++$this->count;
            
            return;
        }
        
        $left = 0;
        $index = $right = $this->last;
        
        while (true) {
            $current = $this->buckets[$index];
            $compare = $this->comparator->compare($signal->item, $current->item);
            
            if ($compare > 0) {
                if ($index < $right) {
                    $left = $index + 1;
                    $index = $left + (int) (($right - $index) / 2);
                    
                    continue;
                }
                
                if ($this->count < $this->limit) {
                    
                    if ($index < $this->last) {
                        $this->shiftBuckets($index + 1, $this->last);
                    }
                    
                    $this->buckets[$index + 1] = $current->append($signal->item);
                    
                    ++$this->count;
                    ++$this->last;
                    
                } elseif ($this->limit > 1) {
                    if ($index === $this->last) {
                        return;
                    }
                    
                    $this->buckets[$this->last]->clear();
                    $this->shiftBuckets($index + 1, $this->last - 1);
                    
                    $this->buckets[$index + 1] = $current->append($signal->item);
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
            
            if ($this->count < $this->limit) {
                $this->shiftBuckets($index, $this->last);
                $this->buckets[$index] = $current->prepend($signal->item);
                
                ++$this->count;
                ++$this->last;
                
            } elseif ($this->limit > 1) {
                $this->buckets[$this->last]->clear();
                $this->shiftBuckets($index, $this->last - 1);
                $this->buckets[$index] = $current->prepend($signal->item);
            } else {
                $current->replace($signal->item);
            }
            
            return;
        }
    }
    
    private function shiftBuckets(int $from, int $last): void
    {
        for ($i = $last; $i >= $from; --$i) {
            $this->buckets[$i + 1] = $this->buckets[$i];
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($this->count === 0) {
            return false;
        }
        
        $producer = new BucketListIterator($this->buckets);
        
        if ($this->next instanceof DataCollector) {
            $signal->continueFrom($this->next);
            
            return $this->next->collectDataFromProducer($producer, $signal, true);
        }
        
        $signal->restartWith($producer, $this->next);
        
        return true;
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($producer->feed($item) as $_) {
            $this->handle($signal);
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        if (!empty($data)) {
            $item = $signal->item;
            
            foreach ($data as $item->key => $item->value) {
                $this->handle($signal);
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    /**
     * @param Item[] $items
     */
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        if (!empty($items)) {
            $current = $signal->item;
            
            foreach ($items as $item) {
                $current->key = $item->key;
                $current->value = $item->value;
                
                $this->handle($signal);
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function applyLimit(int $limit): bool
    {
        if ($limit > 0) {
            $this->limit = \min($limit, $this->limit);
            return true;
        }
        
        $this->limit = 1;
        
        return false;
    }
    
    public function limit(): int
    {
        return $this->limit;
    }
    
    public function isReindexed(): bool
    {
        return $this->reindex;
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