<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;

abstract class AccumulateSeparate extends BaseOperation implements Reindexable
{
    protected Filter $filter;
    
    /** @var array<string|int, mixed> */
    protected array $data = [];
    
    protected int $index = -1;
    
    private bool $reindex;
    
    final protected function __construct(Filter $filter, bool $reindex = false)
    {
        $this->filter = $filter;
        $this->reindex = $reindex;
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty && !empty($this->data)) {
            $signal->resume();
            
            $signal->item->key = ++$this->index;
            $signal->item->value = $this->data;
            $this->data = [];
            
            $this->next->handle($signal);
            
            return true;
        }
        
        return parent::streamingFinished($signal);
    }
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->data = [];
            
            parent::destroy();
        }
    }
    
    final public function isReindexed(): bool
    {
        return $this->reindex;
    }
}