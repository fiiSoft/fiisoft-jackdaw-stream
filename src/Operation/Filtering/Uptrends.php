<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;
use FiiSoft\Jackdaw\Operation\Filtering\Uptrends\UptrendsKeepKeys;
use FiiSoft\Jackdaw\Operation\Filtering\Uptrends\UptrendsReindexKeys;

abstract class Uptrends extends BaseOperation implements Reindexable
{
    protected ItemComparator $comparator;
    protected Comparison $comparison;
    protected ?Item $previous = null;
    
    protected array $trend = [];
    protected int $index = 0;
    protected bool $downtrend;
    
    private bool $reindex;
    
    /**
     * @param Comparable|callable|null $comparison
     */
    final public static function create(bool $reindex = false, bool $downtrend = false, $comparison = null): self
    {
        return $reindex
            ? new UptrendsReindexKeys($reindex, $downtrend, $comparison)
            : new UptrendsKeepKeys($reindex, $downtrend, $comparison);
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    final protected function __construct(bool $reindex = false, bool $downtrend = false, $comparison = null)
    {
        $this->reindex = $reindex;
        $this->downtrend = $downtrend;
        $this->comparison = Comparison::prepare($comparison);
    }
    
    final public function streamingFinished(Signal $signal): bool
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
    
    final public function isReindexed(): bool
    {
        return $this->reindex;
    }
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->trend = [];
            
            parent::destroy();
        }
    }
}