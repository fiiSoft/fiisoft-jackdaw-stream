<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence;

use FiiSoft\Jackdaw\Memo\Entry;

final class InfiniteSequenceMemo extends BaseSequenceMemo
{
    /**
     * @inheritDoc
     */
    public function write($value, $key): void
    {
        $this->sequence->entries[] = new Entry($key, $value);
    }
    
    public function isFull(): bool
    {
        return false;
    }
}