<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Reindex extends BaseOperation
{
    private int $index;
    private int $step;
    
    public function __construct(int $start = 0, int $step = 1)
    {
        if ($step === 0) {
            throw InvalidParamException::describe('step', $step);
        }
        
        $this->index = $start;
        $this->step = $step;
    }
    
    public function handle(Signal $signal): void
    {
        $signal->item->key = $this->index;
        $this->index += $this->step;
    
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            yield $this->index => $value;
        
            $this->index += $this->step;
        }
    }
    
    public function mergeWith(Reindex $other): void
    {
        $this->index = $other->index;
        $this->step = $other->step;
    }
    
    public function isDefaultReindex(): bool
    {
        return $this->index === 0 && $this->step === 1;
    }
}