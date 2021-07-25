<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Stream;

final class Collect extends FinalOperation implements ResultProvider
{
    private $collected = [];
    
    public function __construct(Stream $stream)
    {
        parent::__construct($stream, $this);
    }
    
    public function handle(Signal $signal): void
    {
        $this->collected[$signal->item->key] = $signal->item->value;
    }
    
    public function hasResult(): bool
    {
        return !empty($this->collected);
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->collected);
    }
}