<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Stream;

final class First extends FinalOperation implements ResultProvider
{
    private ?Item $item = null;
    
    /**
     * @param Stream $stream
     * @param callable|mixed|null $orElse
     */
    public function __construct(Stream $stream, $orElse = null)
    {
        parent::__construct($stream, $this, $orElse);
    }
    
    public function handle(Signal $signal): void
    {
        $this->item = $signal->item->copy();
        
        $signal->stop();
    }
    
    public function hasResult(): bool
    {
        return $this->item !== null;
    }
    
    public function getResult(): Item
    {
        return $this->item;
    }
}