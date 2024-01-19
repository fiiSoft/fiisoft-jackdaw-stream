<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

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
            $signal->continueWith($this->flattener->setIterable($signal->item->value), $this->next);
        } else {
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_iterable($value)) {
                yield from $this->flattener->setIterable($value);
            } else {
                yield $key => $value;
            }
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
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->flattener->destroy();
            
            parent::destroy();
        }
    }
}