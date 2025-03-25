<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Operation\Internal\DispatchReady;

interface MemoWriter extends ConsumerReady, DispatchReady
{
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function write($value, $key): void;
}