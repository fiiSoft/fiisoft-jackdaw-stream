<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class EveryNth extends BaseOperation
{
    private int $num, $count;
    
    public function __construct(int $num)
    {
        if ($num < 1) {
            throw new \InvalidArgumentException('Invalid param num');
        }
        
        $this->num = $num;
        $this->count = $num - 1;
    }
    
    public function handle(Signal $signal): void
    {
        if (++$this->count === $this->num) {
            $this->count = 0;
            $this->next->handle($signal);
        }
    }
    
    public function num(): int
    {
        return $this->num;
    }
}