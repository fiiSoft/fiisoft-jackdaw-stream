<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\HandlerReady;

interface MemoWriter extends ConsumerReady, HandlerReady
{
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function write($value, $key): void;
}