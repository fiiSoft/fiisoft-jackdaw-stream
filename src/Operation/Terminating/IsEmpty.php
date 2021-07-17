<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Stream;

final class IsEmpty extends FinalOperation implements ResultProvider
{
    private bool $isEmpty;
    
    public function __construct(Stream $stream, bool $initial)
    {
        $this->isEmpty = $initial;
        
        parent::__construct($stream, $this);
    }
    
    public function handle(Signal $signal): void
    {
        $this->isEmpty = !$this->isEmpty;
        
        $signal->stop();
    }
    
    public function hasResult(): bool
    {
        return true;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->isEmpty);
    }
}