<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Adapter;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Memo\MemoWriter;

final class MemoWriterAdapter extends PrimitiveDispatchHandler
{
    private MemoWriter $memo;
    
    public function __construct(MemoWriter $memo)
    {
        $this->memo = $memo;
    }
    
    public function handle(Signal $signal): void
    {
        $this->memo->write($signal->item->value, $signal->item->key);
    }
    
    /**
     * @inheritDoc
     */
    public function handlePair($value, $key): void
    {
        $this->memo->write($value, $key);
    }
}