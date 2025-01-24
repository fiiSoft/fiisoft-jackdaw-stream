<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\ValueRef\Adapter;

use FiiSoft\Jackdaw\Memo\MemoReader;

final class MemoIntValue extends VolatileIntValue
{
    private MemoReader $reader;
    
    public function __construct(MemoReader $reader)
    {
        $this->reader = $reader;
    }
    
    public function int(): int
    {
        return $this->reader->read();
    }
}