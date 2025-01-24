<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader;

final class ValueReader extends BaseSequenceReader
{
    /**
     * @inheritDoc
     */
    public function read()
    {
        return $this->sequence->entries[$this->index]->value;
    }
}