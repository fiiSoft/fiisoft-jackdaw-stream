<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk\ManyKeepsKeys;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk\ManyReindexKeys;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk\OneKeepKeys;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk\OneReindexKeys;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk\VolatileKeepsKeys;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk\VolatileReindexKeys;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use FiiSoft\Jackdaw\ValueRef\IntProvider;

abstract class Chunk extends BaseOperation implements Reindexable
{
    /** @var array<string|int, mixed> */
    protected array $chunked = [];
    
    protected int $index = 0;
    protected int $count = 0;
    
    private bool $reindex;
    
    /**
     * @param IntProvider|\Traversable<int>|iterable<int>|callable|int $size
     */
    final public static function create($size, bool $reindex = false): self
    {
        $size = IntNum::getAdapter($size);
        
        if ($size->isConstant()) {
            if ($size->int() === 1) {
                return $reindex
                    ? new OneReindexKeys($reindex)
                    : new OneKeepKeys($reindex);
            }
            
            return $reindex
                ? new ManyReindexKeys($size->int(), $reindex)
                : new ManyKeepsKeys($size->int(), $reindex);
        }
        
        return $reindex
            ? new VolatileReindexKeys($size, $reindex)
            : new VolatileKeepsKeys($size, $reindex);
    }
    
    protected function __construct(bool $reindex = false)
    {
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