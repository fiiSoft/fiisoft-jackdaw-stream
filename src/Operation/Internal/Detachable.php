<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Operation\Operation;

interface Detachable extends Operation
{
    public function makeDetachedCopy(): self;
}