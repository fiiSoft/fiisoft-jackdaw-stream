<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Operation;

abstract class Collaborator extends BaseStreamPipe
{
    /**
     * @param Operation $operation
     * @param Item[] $items
     * @return void
     */
    abstract protected function restartFrom(Operation $operation, array $items);
    
    /**
     * @param Operation $operation
     * @param Item[] $items
     * @return void
     */
    abstract protected function continueFrom(Operation $operation, array $items);
    
    /**
     * @param Operation $operation
     * @return void
     */
    abstract protected function limitReached(Operation $operation);
    
    /**
     * @return void
     */
    abstract protected function streamIsEmpty();
}