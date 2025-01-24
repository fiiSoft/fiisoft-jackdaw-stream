<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Memo\MemoReader;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class MemoReaderAdapter extends BaseProducer
{
    private MemoReader $reader;
    
    private int $index = 0;
    
    public function __construct(MemoReader $reader)
    {
        $this->reader = $reader;
    }
    
    public function getIterator(): \Generator
    {
        $value = $this->reader->read();
        
        while ($value !== null) {
            yield $this->index++ => $value;
            
            $value = $this->reader->read();
        }
    }
}