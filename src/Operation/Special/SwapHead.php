<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

abstract class SwapHead extends BaseOperation
{
    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    final public function buildStream(iterable $stream): iterable
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
}