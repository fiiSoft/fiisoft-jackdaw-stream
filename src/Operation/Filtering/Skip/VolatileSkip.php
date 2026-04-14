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
        if ($this->isActive) {
            if ($this->count >= $this->offset->int()) {
                $this->isActive = false;
                $signal->forget($this);
                $this->next->handle($signal);
            } else {
                ++$this->count;
            }
        } else {
            $this->next->handle($signal);
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
    
    protected function __clone()
    {
        $this->isActive = true;
        $this->count = 0;
        
        parent::__clone();
    }
}