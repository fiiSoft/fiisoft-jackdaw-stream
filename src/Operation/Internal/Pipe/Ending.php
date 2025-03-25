<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\Pipe;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

final class Ending implements Operation
{
    private Operation $prev;
    
    public function __construct(Initial $prev)
    {
        $this->prev = $prev;
    }
    
    public function handle(Signal $signal): void
    {
        //noop
    }
    
    public function assignStream(Stream $stream): void
    {
        //noop
    }
    
    public function setNext(Operation $next, bool $direct = false): Operation
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }

    public function setPrev(Operation $prev): void
    {
        $this->prev = $prev;
    }
    
    public function prepend(Operation $operation): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    public function getNext(): ?Operation
    {
        return null;
    }
    
    public function getPrev(): Operation
    {
        return $this->prev;
    }
    
    public function getLast(): Operation
    {
        return $this->prev;
    }
    
    public function removeFromChain(): Operation
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        return false;
    }
    
    public function destroy(): void
    {
        //noop
    }
    
    public function buildStream(iterable $stream): iterable
    {
        return $stream;
    }
    
    public function resume(): void
    {
        //noop
    }
    
    public function prepare(): void
    {
        //noop
    }
}