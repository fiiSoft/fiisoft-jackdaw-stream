<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\ValueRef\Exception\WrongIntValueException;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use FiiSoft\Jackdaw\ValueRef\IntProvider;

final class ReadNext extends CountableRead
{
    private int $times = 0;
    private int $countReadings = -1;
    
    /**
     * @param IntProvider|iterable<int>|callable|int $howMany
     */
    public function __construct($howMany)
    {
        parent::__construct($howMany);
    }
    
    public function handle(Signal $signal): void
    {
        if (++$this->countReadings === 0) {
            $this->times = $this->howMany->int();
            
            if ($this->times > 0) {
                $signal->swapHead($this);
            } elseif ($this->times === 0) {
                $this->countReadings = -1;
                $this->next->handle($signal);
            } else {
                throw WrongIntValueException::invalidNumber($this->howMany, $this->times);
            }
        } elseif($this->countReadings === $this->times) {
            $this->countReadings = -1;
            
            $signal->restoreHead();
            $this->next->handle($signal);
        }
    }
    
    public function mergeWith(ReadNext $other): void
    {
        $this->howMany = IntNum::addArgs($this->howMany, $other->howMany);
    }
}