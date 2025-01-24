<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\SingleMemo;

final class KeyMemo extends BaseSingleMemo
{
    /**
     * @inheritDoc
     */
    public function write($value, $key): void
    {
        $this->value = $key;
    }
}