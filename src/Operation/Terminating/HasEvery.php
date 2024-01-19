<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery\AnyHasEvery;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery\BothHasEvery;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery\KeyHasEvery;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery\ValueHasEvery;
use FiiSoft\Jackdaw\Stream;

abstract class HasEvery extends SimpleFinal
{
    protected array $values;
    
    protected bool $hasEvery = false;
    
    final public static function create(Stream $stream, array $values, int $mode = Check::VALUE): self
    {
        $values = \array_unique($values, \SORT_REGULAR);
        
        switch (Check::getMode($mode)) {
            case Check::VALUE:
                return new ValueHasEvery($stream, $values);
            case Check::KEY:
                return new KeyHasEvery($stream, $values);
            case Check::BOTH:
                return new BothHasEvery($stream, $values);
            default:
                return new AnyHasEvery($stream, $values);
        }
    }
    
    protected function __construct(Stream $stream, array $values)
    {
        parent::__construct($stream);
        
        $this->values = $values;
    }
    
    final public function hasResult(): bool
    {
        return true;
    }
    
    final public function getResult(): Item
    {
        return new Item(0, $this->hasEvery);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->values = [];
            
            parent::destroy();
        }
    }
}