<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\Interruption;
use FiiSoft\Jackdaw\Internal\Signal;

final class Iterate extends BaseOperation
{
    public function handle(Signal $signal): void
    {
        throw new Interruption();
    }
}