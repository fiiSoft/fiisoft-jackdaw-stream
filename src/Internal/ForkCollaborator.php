<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;
use FiiSoft\Jackdaw\Operation\LastOperation;
use FiiSoft\Jackdaw\Stream;

abstract class ForkCollaborator extends ProtectedCloning
{
    //StreamFork->, FinalOperation->, *Stream
    protected function getLastOperation(): ?LastOperation
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //StreamFork->, *FinalOperation->, *Stream
    protected function cloneStream(): Stream
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //*Result->, StreamFork->, StreamPipeAdapter->, Feed->, FeedMany->, *FinalOperation->, *Stream
    protected function process(Signal $signal): bool
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //Feed->, FeedMany->, *Stream
    protected function assignParent(Stream $stream): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    //[P]*CommonOperationCode, *[P]Feed, *[P]FeedMany, *[P]FinalOperation, *[P]Signal, *Stream
    //Feed->, FeedMany->, FinalOperation->
    protected function resume(): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
}