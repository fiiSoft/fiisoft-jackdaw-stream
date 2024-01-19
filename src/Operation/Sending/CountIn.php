<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class CountIn extends BaseOperation
{
    private int $counter;
    
    /**
     * @param int $counter REFERENCE
     */
    public function __construct(int &$counter)
    {
        $this->counter = &$counter;
    }
    
    public function handle(Signal $signal): void
    {
        ++$this->counter;
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            ++$this->counter;
            
            yield $key => $value;
        }
    }
}