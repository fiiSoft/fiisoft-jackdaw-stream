<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Internal\Collection\BaseStreamCollection;
use FiiSoft\Jackdaw\Operation\Internal\GroupingOperation;
use FiiSoft\Jackdaw\Operation\Terminating\GroupBy\GroupByReindexKeys;

abstract class GroupBy extends GroupingOperation
{
    /**
     * @param DiscriminatorReady|callable|array|string|int $discriminator
     */
    final public static function create($discriminator, ?bool $reindex = null): self
    {
        return self::shouldReindex($discriminator, $reindex)
            ? new GroupByReindexKeys($discriminator)
            : new GroupBy\GroupByKeepKeys($discriminator);
    }
    
    final public function result(): BaseStreamCollection
    {
        return BaseStreamCollection::create($this, $this->collections);
    }
}