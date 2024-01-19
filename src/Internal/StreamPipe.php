<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;

abstract class StreamPipe extends ForkCollaborator
{
    //Result, DispatchOperation, FinalOperation, Stream
    protected function prepareSubstream(bool $isLoop): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //BaseStreamIterator, DispatchOperation, FinalOperation, Stream
    protected function continueIteration(bool $once = false): bool
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //BaseFastIterator, BaseStreamIterator, Stream
    protected function finish(): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //FinalOperation, Stream
    protected function refreshResult(): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //Result, Stream
    protected function execute(): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
}