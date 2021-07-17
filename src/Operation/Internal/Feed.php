<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\BaseStreamPipe;
use FiiSoft\Jackdaw\Internal\Signal;

final class Feed extends BaseOperation
{
    private ?BaseStreamPipe $stream;
    
    public function __construct(BaseStreamPipe $stream)
    {
        $this->stream = $stream;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->stream !== null && !$signal->sendTo($this->stream)) {
            $this->stream = null;
        }
        
        $this->next->handle($signal);
    }
}