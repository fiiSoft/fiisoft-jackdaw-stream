<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;
use FiiSoft\Jackdaw\Operation\Mapping\Accumulate\AccumulateKeepKeys;
use FiiSoft\Jackdaw\Operation\Mapping\Accumulate\AccumulateReindexKeys;

abstract class Accumulate extends BaseOperation implements Reindexable
{
    protected Filter $filter;
    
    /** @var array<string|int, mixed> */
    protected array $data = [];
    
    protected int $index = 0;
    protected bool $reverse;
    
    private bool $reindex;
    
    /**
     * @param Filter|callable|mixed $filter
     */
    final public static function create(
        $filter,
        ?int $mode = null,
        bool $reindex = false,
        bool $reverse = false
    ): self
    {
        $filter = Filters::getAdapter($filter, $mode);
        
        return $reindex
            ? new AccumulateReindexKeys($filter, $reverse, $reindex)
            : new AccumulateKeepKeys($filter, $reverse, $reindex);
    }
    
    final protected function __construct(Filter $filter, bool $reverse = false, bool $reindex = false)
    {
        $this->filter = $filter;
        $this->reverse = $reverse;
        $this->reindex = $reindex;
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty && !empty($this->data)) {
            $signal->resume();
            
            $signal->item->key = $this->index++;
            $signal->item->value = $this->data;
            $this->data = [];
            
            $this->next->handle($signal);
            
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
            $this->data = [];
            
            parent::destroy();
        }
    }
}