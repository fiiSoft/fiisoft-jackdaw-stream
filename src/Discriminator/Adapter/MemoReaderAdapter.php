<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\Adapter;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Memo\MemoReader;

final class MemoReaderAdapter implements Discriminator
{
    private MemoReader $reader;
    
    public function __construct(MemoReader $reader)
    {
        $this->reader = $reader;
    }
    
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null)
    {
        return $this->reader->read();
    }
}