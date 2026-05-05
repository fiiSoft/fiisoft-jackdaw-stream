<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Internal\DataAware;
use FiiSoft\Jackdaw\Internal\Helper;
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
        return Helper::shouldReindex($discriminator, $reindex)
            ? new CategorizeReindexKeys($discriminator)
            : new CategorizeKeepKeys($discriminator);
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        $data = $this->collections;
        $this->collections = [];
        
        if ($this->next instanceof DataAware) {
            $this->next->setCollectedData($data);
            $data = [];
        }
        
        $signal->restartWith(Producers::getAdapter($data), $this->next);
        
        return true;
    }
}