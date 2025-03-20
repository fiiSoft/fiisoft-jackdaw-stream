<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;
use FiiSoft\Jackdaw\Stream;

abstract class StreamPipe extends ForkCollaborator
{
    //StreamPipeAdapter->, StreamFork->, *Result->, *FinalOperation->, *Stream->, *Source
    protected function prepareSubstream(bool $isLoop): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //StreamFork->, *FinalOperation
    protected function getStream(): Stream
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //BaseStreamIterator->, DispatchOperation->, *FinalOperation->, *Stream->
    protected function continueIteration(bool $once = false): bool
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //BaseFastIterator->, BaseStreamIterator->, *FinalOperation->, *Stream->
    protected function finish(): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
}