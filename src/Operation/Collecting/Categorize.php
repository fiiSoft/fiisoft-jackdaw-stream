<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Categorize\CategorizeKeepKeys;
use FiiSoft\Jackdaw\Operation\Collecting\Categorize\CategorizeReindexKeys;
use FiiSoft\Jackdaw\Operation\Internal\GroupingOperation;
use FiiSoft\Jackdaw\Producer\Producers;

abstract class Categorize extends GroupingOperation
{
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     */
    final public static function create($discriminator, ?bool $reindex = null): self
    {
        return self::shouldReindex($discriminator, $reindex)
            ? new CategorizeReindexKeys($discriminator)
            : new CategorizeKeepKeys($discriminator);
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        $signal->restartWith(Producers::getAdapter($this->collections), $this->next);
        
        return true;
    }
}