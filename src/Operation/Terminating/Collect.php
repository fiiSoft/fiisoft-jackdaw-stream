<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Operation\Internal\Reindexable;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Terminating\Collect\CollectKeepKeys;
use FiiSoft\Jackdaw\Operation\Terminating\Collect\CollectReindexKeys;
use FiiSoft\Jackdaw\Stream;

abstract class Collect extends BaseCollect implements Reindexable
{
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
    
    final public function isReindexed(): bool
    {
        return $this->reindex;
    }
    
    final public function reindexed(): Operation
    {
        return $this->reindex ? $this : self::create($this->stream, true);
    }
}