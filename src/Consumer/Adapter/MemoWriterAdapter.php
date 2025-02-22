<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer\Adapter;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Memo\MemoWriter;

final class MemoWriterAdapter implements Consumer
{
    private MemoWriter $writer;
    
    public function __construct(MemoWriter $writer)
    {
        $this->writer = $writer;
    }
    
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        $this->writer->write($value, $key);
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->writer->write($value, $key);
            
            yield $key => $value;
        }
    }
}