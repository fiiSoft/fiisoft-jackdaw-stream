<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;

final class Uptrends extends BaseOperation implements Reindexable
{
    private ItemComparator $comparator;
    private Comparison $comparison;
    private ?Item $previous = null;
    
    private array $trend = [];
    private int $index = 0;
    private bool $reindex, $downtrend;
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function __construct(bool $reindex = false, bool $downtrend = false, $comparison = null)
    {
        $this->reindex = $reindex;
        $this->downtrend = $downtrend;
        $this->comparison = Comparison::prepare($comparison);
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if ($this->previous === null) {
            $this->previous = $item->copy();
            $this->comparator = ItemComparatorFactory::getForComparison($this->comparison, $this->downtrend);
        } elseif ($this->comparator->compare($this->previous, $item) < 0) {
            if ($this->reindex) {
                $this->trend[] = $this->previous->value;
            } else {
                $this->trend[$this->previous->key] = $this->previous->value;
            }
            
            $this->previous->key = $item->key;
            $this->previous->value = $item->value;
        } else {
            if (!empty($this->trend)) {
                if ($this->reindex) {
                    $this->trend[] = $this->previous->value;
                } else {
                    $this->trend[$this->previous->key] = $this->previous->value;
                }
            }
            
            $this->previous->key = $item->key;
            $this->previous->value = $item->value;
            
            if (!empty($this->trend)) {
                $item->key = $this->index++;
                $item->value = $this->trend;
                
                $this->next->handle($signal);
                
                $this->trend = [];
            }
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty && !empty($this->trend)) {
            if ($this->reindex) {
                $this->trend[] = $this->previous->value;
            } else {
                $this->trend[$this->previous->key] = $this->previous->value;
            }
            
            $signal->resume();
            $signal->item->key = $this->index++;
            $signal->item->value = $this->trend;
            
            $this->next->handle($signal);
            $this->trend = [];
            
            return true;
        }
        
        return parent::streamingFinished($signal);
    }
    
    public function isReindexed(): bool
    {
        return $this->reindex;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->trend = [];
            
            parent::destroy();
        }
    }
}