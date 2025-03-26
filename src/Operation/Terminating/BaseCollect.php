<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;

abstract class BaseCollect extends SimpleFinal
{
    /** @var array<string|int, mixed> */
    protected array $collected = [];
    
    protected bool $isSelfStream = false;
    
    final public function getIterator(): \Iterator
    {
        if ($this->shouldIterateItself()) {
            $this->isSelfStream = true;
            
            return $this->iterateItself();
        }
        
        return parent::getIterator();
    }
    
    private function shouldIterateItself(): bool
    {
        return $this->result === null
            && empty($this->collected)
            && !$this->isSelfStream
            && $this->stream->canBuildPowerStream();
    }
    
    private function iterateItself(): \Generator
    {
        yield from $this->stream;
    }
    
    final public function getResult(): Item
    {
        return new Item(0, $this->collected);
    }
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->collected = [];
            
            parent::destroy();
        }
    }
}