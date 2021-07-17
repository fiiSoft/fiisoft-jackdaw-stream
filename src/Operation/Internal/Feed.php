<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\BaseStreamPipe;
use FiiSoft\Jackdaw\Internal\Signal;

final class Feed extends BaseOperation
{
    /** @var BaseStreamPipe|null */
    private $stream;
    
    public function __construct(BaseStreamPipe $stream)
    {
        $this->stream = $stream;
    }
    
    public function handle(Signal $signal)
    {
        if ($this->stream !== null && !$signal->sendTo($this->stream)) {
            $this->stream = null;
        }
        
        $this->next->handle($signal);
    }
}