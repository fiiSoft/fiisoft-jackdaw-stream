<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Memo\MemoWriter;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Remember extends BaseOperation
{
    private MemoWriter $memo;
    
    public function __construct(MemoWriter $memo)
    {
        $this->memo = $memo;
    }
    
    public function handle(Signal $signal): void
    {
        $this->memo->write($signal->item->value, $signal->item->key);
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->memo->write($value, $key);
            
            yield $key => $value;
        }
    }
}