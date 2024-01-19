<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk\ManyKeepsKeys;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk\ManyReindexKeys;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk\OneKeepKeys;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk\OneReindexKeys;

abstract class Chunk extends BaseOperation implements Reindexable
{
    protected array $chunked = [];
    
    protected int $index = 0;
    protected int $size;
    protected int $count = 0;
    
    private bool $reindex;
    
    final public static function create(int $size, bool $reindex = false): self
    {
        if ($size === 1) {
            return $reindex
                ? new OneReindexKeys($size, $reindex)
                : new OneKeepKeys($size, $reindex);
        }
        
        return $reindex
            ? new ManyReindexKeys($size, $reindex)
            : new ManyKeepsKeys($size, $reindex);
    }
    
    final protected function __construct(int $size, bool $reindex = false)
    {
        if ($size < 1) {
            throw InvalidParamException::describe('size', $size);
        }
        
        $this->size = $size;
        $this->reindex = $reindex;
    }
    
    final public function streamingFinished(Signal $signal): bool
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
    
    final public function isReindexed(): bool
    {
        return $this->reindex;
    }
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->chunked = [];
            
            parent::destroy();
        }
    }
}