<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\DataCollector;
use FiiSoft\Jackdaw\Operation\Internal\GroupingOperation;
use FiiSoft\Jackdaw\Producer\Producers;

final class Categorize extends GroupingOperation
{
    public function streamingFinished(Signal $signal): bool
    {
        if ($this->next instanceof DataCollector) {
            $signal->continueFrom($this->next);
            
            return $this->next->acceptSimpleData($this->collections, $signal, false);
        }
        
        $signal->restartWith(Producers::fromArray($this->collections), $this->next);
        
        return true;
    }
}