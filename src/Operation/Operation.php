<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamAware;
use FiiSoft\Jackdaw\Internal\StreamBuilder;

interface Operation extends StreamAware, Destroyable, StreamBuilder
{
    public function handle(Signal $signal): void;
    
    public function setNext(Operation $next, bool $direct = false): Operation;
    
    public function setPrev(Operation $prev): void;
    
    public function getPrev(): ?Operation;
    
    public function getNext(): ?Operation;
    
    public function getLast(): Operation;
    
    public function removeFromChain(): Operation;
    
    public function prepend(Operation $operation): void;
    
    /**
     * @return bool return true to resume stream processing, false otherwise
     */
    public function streamingFinished(Signal $signal): bool;
    
    public function resume(): void;
    
    public function prepare(): void;
}