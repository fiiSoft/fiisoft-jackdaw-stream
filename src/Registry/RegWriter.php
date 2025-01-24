<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry;

use FiiSoft\Jackdaw\Memo\MemoWriter;

interface RegWriter extends MemoWriter
{
    /**
     * @param mixed $value
     */
    public function set($value): void;
}