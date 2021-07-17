<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\Signal;

final class Initial extends BaseOperation
{
    public function handle(Signal $signal)
    {
        $this->next->handle($signal);
    }
}