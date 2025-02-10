<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader;

final class TupleReader extends BaseSequenceReader
{
    /**
     * @return array{string|int, mixed}
     */
    public function read(): array
    {
        return $this->sequence->get($this->index)->asTuple();
    }
}