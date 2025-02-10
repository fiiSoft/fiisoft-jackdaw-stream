<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Skip;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\Skip;
use FiiSoft\Jackdaw\ValueRef\IntValue;

final class VolatileSkip extends Skip
{
    private IntValue $offset;
    
    private int $count = 0;
    private bool $isActive = true;
    
    protected function __construct(IntValue $offset)
    {
        parent::__construct();
        
        $this->offset = $offset;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->count >= $this->offset->int()) {
            $this->next->handle($signal);
            $signal->forget($this);
        } else {
            ++$this->count;
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->isActive) {
                if ($this->count++ >= $this->offset->int()) {
                    $this->isActive = false;
                } else {
                    continue;
                }
            }
            
            yield $key => $value;
        }
    }
    
    protected function offset(): IntValue
    {
        return $this->offset;
    }
}