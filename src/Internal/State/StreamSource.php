<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;
use FiiSoft\Jackdaw\Internal\Collaborator;

abstract class StreamSource extends Collaborator
{
    //Source, Stream
    protected function setSource(Source $state): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
}