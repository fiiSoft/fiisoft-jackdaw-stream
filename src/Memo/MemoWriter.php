<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

use FiiSoft\Jackdaw\Consumer\ConsumerReady;

interface MemoWriter extends ConsumerReady
{
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function write($value, $key): void;
}