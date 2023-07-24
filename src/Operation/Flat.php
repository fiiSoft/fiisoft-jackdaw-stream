<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Producer\Generator\Flattener;
use FiiSoft\Jackdaw\Producer\Producers;

final class Flat extends BaseOperation
{
    private Flattener $flattener;
    
    /**
     * @param int $level 0 means no nesting restrictions (well, almost)
     */
    public function __construct(int $level = 0)
    {
        $this->flattener = Producers::flattener([], $level);
    }
    
    public function handle(Signal $signal): void
    {
        if (\is_iterable($signal->item->value)) {
            $this->flattener->setIterable($signal->item->value);
            $signal->continueWith($this->flattener, $this->next);
        } else {
            $this->next->handle($signal);
        }
    }
    
    public function mergeWith(Flat $other): void
    {
        $this->flattener->increaseLevel($other->maxLevel());
    }
    
    public function maxLevel(): int
    {
        return $this->flattener->maxLevel();
    }
    
    public function isLevel(int $level): bool
    {
        return $this->flattener->isLevel($level);
    }
    
    public function decreaseLevel(): void
    {
        $this->flattener->decreaseLevel();
    }
    
    protected function __clone()
    {
        $this->flattener = clone $this->flattener;
        
        parent::__clone();
    }
}