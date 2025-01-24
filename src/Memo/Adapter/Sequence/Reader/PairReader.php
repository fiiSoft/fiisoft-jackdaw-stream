<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader;

final class PairReader extends BaseSequenceReader
{
    /**
     * @return array<string|int, mixed>
     */
    public function read(): array
    {
        return $this->sequence->entries[$this->index]->asPair();
    }
}