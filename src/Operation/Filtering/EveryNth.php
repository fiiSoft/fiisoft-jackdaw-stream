<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class EveryNth extends BaseOperation
{
    private int $num = 1, $count = 0;
    
    public function __construct(int $num)
    {
        $this->applyNum($num);
    }
    
    public function handle(Signal $signal): void
    {
        if (++$this->count === $this->num) {
            $this->next->handle($signal);
            $this->count = 0;
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (++$this->count === $this->num) {
                $this->count = 0;
                
                yield $key => $value;
            }
        }
    }
    
    public function applyNum(int $num): void
    {
        if ($num < 1) {
            throw InvalidParamException::describe('num', $num);
        }
        
        $this->num *= $num;
        $this->count = $this->num - 1;
    }
    
    public function num(): int
    {
        return $this->num;
    }
}