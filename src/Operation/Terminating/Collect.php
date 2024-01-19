<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;
use FiiSoft\Jackdaw\Operation\Terminating\Collect\CollectKeepKeys;
use FiiSoft\Jackdaw\Operation\Terminating\Collect\CollectReindexKeys;
use FiiSoft\Jackdaw\Stream;

abstract class Collect extends SimpleFinal implements Reindexable
{
    protected array $collected = [];
    
    private bool $reindex;
    
    final public static function create(Stream $stream, bool $reindex = false): self
    {
        return $reindex
            ? new CollectReindexKeys($stream, $reindex)
            : new CollectKeepKeys($stream, $reindex);
    }
    
    final protected function __construct(Stream $stream, bool $reindex = false)
    {
        parent::__construct($stream);
        
        $this->reindex = $reindex;
    }
    
    final public function hasResult(): bool
    {
        return true;
    }
    
    final public function getResult(): Item
    {
        return new Item(0, $this->collected);
    }
    
    final public function isReindexed(): bool
    {
        return $this->reindex;
    }
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->collected = [];
            
            parent::destroy();
        }
    }
}