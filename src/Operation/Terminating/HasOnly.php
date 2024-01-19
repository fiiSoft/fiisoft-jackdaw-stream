<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Terminating\HasOnly\AnyHasOnly;
use FiiSoft\Jackdaw\Operation\Terminating\HasOnly\BothHasOnly;
use FiiSoft\Jackdaw\Operation\Terminating\HasOnly\KeyHasOnly;
use FiiSoft\Jackdaw\Operation\Terminating\HasOnly\ValueHasOnly;
use FiiSoft\Jackdaw\Stream;

abstract class HasOnly extends SimpleFinal
{
    protected array $values;
    
    protected bool $hasOnly = true;
    
    final public static function create(Stream $stream, array $values, int $mode = Check::VALUE): self
    {
        switch (Check::getMode($mode)) {
            case Check::VALUE:
                return new ValueHasOnly($stream, $values);
            case Check::KEY:
                return new KeyHasOnly($stream, $values);
            case Check::BOTH:
                return new BothHasOnly($stream, $values);
            default:
                return new AnyHasOnly($stream, $values);
        }
    }
    
    final protected function __construct(Stream $stream, array $values)
    {
        $this->values = \array_unique($values, \SORT_REGULAR);
        
        parent::__construct($stream);
    }
    
    final public function hasResult(): bool
    {
        return true;
    }
    
    final public function getResult(): Item
    {
        return new Item(0, $this->hasOnly);
    }
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->values = [];
            
            parent::destroy();
        }
    }
}