<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader;

final class KeyReader extends BaseSequenceReader
{
    /**
     * @inheritDoc
     */
    public function read()
    {
        return $this->sequence->entries[$this->index]->key;
    }
}