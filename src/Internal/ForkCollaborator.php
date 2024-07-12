<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;
use FiiSoft\Jackdaw\Operation\Terminating\FinalOperation;
use FiiSoft\Jackdaw\Stream;

abstract class ForkCollaborator extends ProtectedCloning
{
    //Fork, FinalOperation
    protected function getFinalOperation(): ?FinalOperation
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //Result, StreamPipeAdapter, FinalOperation, ProcessOperation [Feed, FeedMany, Fork]
    protected function process(Signal $signal): bool
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //Fork, FinalOperation
    protected function cloneStream(): Stream
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //Feed, FeedMany, Stream
    protected function assignParent(Stream $stream): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //Feed, FeedMany, Stream, Operation
    protected function resume(): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
}