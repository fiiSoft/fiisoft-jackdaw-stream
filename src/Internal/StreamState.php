<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;

abstract class StreamState
{
    /**
     * Used in: Counter, Result
     *
     * @return bool true when Stream has not been started yet
     */
    protected function isNotStartedYet(): bool
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
}