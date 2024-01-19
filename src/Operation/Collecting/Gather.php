<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Gather\GatherKeepKeys;
use FiiSoft\Jackdaw\Operation\Collecting\Gather\GatherReindexKeys;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;
use FiiSoft\Jackdaw\Producer\Producers;

abstract class Gather extends BaseOperation implements Reindexable
{
    protected array $data = [];
    private bool $reindex;
    
    final public static function create(bool $reindex = false): self
    {
        return $reindex ? new GatherReindexKeys($reindex) : new GatherKeepKeys($reindex);
    }
    
    final protected function __construct(bool $reindex = false)
    {
        $this->reindex = $reindex;
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        if (empty($this->data)) {
            return parent::streamingFinished($signal);
        }
        
        $signal->restartWith(Producers::getAdapter([$this->data]), $this->next);
        
        return true;
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
    
    final public function reindexed(): self
    {
        return $this->reindex ? $this : self::create(true);
    }
}