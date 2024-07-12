<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;

abstract class StreamState
{
    //Counter, Result, Stream
    protected function isNotStartedYet(): bool
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
}