<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special\ReadMany;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Special\ReadMany;
use FiiSoft\Jackdaw\ValueRef\Exception\WrongIntValueException;

final class ReadManyReindexKeys extends ReadMany
{
    private int $index = -1;
    private int $times = 0;
    private int $countReadings = -1;
    
    public function handle(Signal $signal): void
    {
        if (++$this->countReadings === 0) {
            $this->times = $this->howMany->int();
            
            if ($this->times > 0) {
                $this->index = -1;
                $signal->swapHead($this);
            } elseif ($this->times === 0) {
                $this->countReadings = -1;
            } else {
                throw WrongIntValueException::invalidNumber($this->howMany, $this->times);
            }
        } else {
            if ($this->countReadings === $this->times) {
                $this->countReadings = -1;
                $signal->restoreHead();
            }
            
            $signal->item->key = ++$this->index;
            
            $this->next->handle($signal);
        }
    }
    
    public function reindexKeys(): bool
    {
        return true;
    }
}