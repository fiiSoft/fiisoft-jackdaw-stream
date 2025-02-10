<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Skip;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\Skip;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use FiiSoft\Jackdaw\ValueRef\IntValue;

final class ConstantSkip extends Skip
{
    private int $offset;
    private int $count = 0;
    private bool $isActive = true;
    
    protected function __construct(int $offset)
    {
        parent::__construct();
        
        if ($offset < 0) {
            throw InvalidParamException::describe('offset', $offset);
        }
        
        $this->offset = $offset;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->count === $this->offset) {
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
                if ($this->count++ === $this->offset) {
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
        return IntNum::constant($this->offset);
    }
}