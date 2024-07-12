<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;
use FiiSoft\Jackdaw\Operation\Mapping\ChunkBy\ChunkByKeepKeys;
use FiiSoft\Jackdaw\Operation\Mapping\ChunkBy\ChunkByReindexKeys;

abstract class ChunkBy extends BaseOperation implements Reindexable
{
    protected Discriminator $discriminator;
    
    /** @var array<string|int, mixed> */
    protected array $chunked = [];
    
    /** @var string|int|bool|null */
    protected $previous = null;
    
    private bool $reindex;
    
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     */
    final public static function create($discriminator, bool $reindex = false): self
    {
        return $reindex
            ? new ChunkByReindexKeys($discriminator, $reindex)
            : new ChunkByKeepKeys($discriminator, $reindex);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     */
    final protected function __construct($discriminator, bool $reindex = false)
    {
        $this->discriminator = Discriminators::prepare($discriminator);
        $this->reindex = $reindex;
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty && !empty($this->chunked)) {
            $signal->resume();
    
            $signal->item->value = $this->chunked;
            $signal->item->key = $this->previous;
            
            $this->chunked = [];
            $this->previous = null;
    
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
            $this->previous = null;
            $this->chunked = [];
            
            parent::destroy();
        }
    }
}