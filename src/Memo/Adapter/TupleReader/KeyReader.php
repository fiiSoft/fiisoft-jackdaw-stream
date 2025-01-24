<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\TupleReader;

final class KeyReader extends BaseTupleReader
{
    /**
     * @inheritDoc
     */
    public function read()
    {
        return $this->tuple->key;
    }
}