<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Stream;

final class Count extends FinalOperation implements ResultProvider
{
    private int $count = 0;
    
    public function __construct(Stream $stream)
    {
        parent::__construct($stream, $this);
    }
    
    public function handle(Signal $signal): void
    {
        ++$this->count;
    }
    
    public function hasResult(): bool
    {
        return true;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->count);
    }
}