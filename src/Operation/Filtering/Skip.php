<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Skip extends BaseOperation
{
    private int $offset;
    private int $count = 0;
    private bool $isActive = true;
    
    public function __construct(int $offset)
    {
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
    
    public function mergeWith(Skip $other): void
    {
        $this->offset += $other->offset;
    }
}