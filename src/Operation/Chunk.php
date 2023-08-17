<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;

final class Chunk extends BaseOperation implements Reindexable
{
    private int $size;
    private int $count = 0;
    private int $index = 0;
    
    private array $chunked = [];
    private bool $reindex;
    
    public function __construct(int $size, bool $reindex = false)
    {
        if ($size < 1) {
            throw new \InvalidArgumentException('Invalid param size');
        }
        
        $this->size = $size;
        $this->reindex = $reindex;
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if ($this->size === 1) {
            $item->value = [$this->reindex ? 0 : $item->key => $item->value];
            $item->key = $this->index++;
            
            $this->next->handle($signal);
            return;
        }
        
        if ($this->reindex) {
            $this->chunked[] = $item->value;
        } else {
            $this->chunked[$item->key] = $item->value;
        }
    
        if (++$this->count === $this->size) {
            $item->key = $this->index++;
            $item->value = $this->chunked;
    
            $this->count = 0;
            $this->chunked = [];
    
            $this->next->handle($signal);
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty && !empty($this->chunked)) {
            $signal->resume();
            
            $signal->item->key = $this->index++;
            $signal->item->value = $this->chunked;
            
            $this->count = 0;
            $this->chunked = [];
            
            $this->next->handle($signal);
            
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
            $this->chunked = [];
            
            parent::destroy();
        }
    }
}