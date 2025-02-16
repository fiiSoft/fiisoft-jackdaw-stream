<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Stream;

final class IsEmpty extends SimpleFinal
{
    private bool $isEmpty;
    
    public function __construct(Stream $stream, bool $isEmpty)
    {
        $this->isEmpty = $isEmpty;
        
        parent::__construct($stream);
    }
    
    public function handle(Signal $signal): void
    {
        $this->isEmpty = !$this->isEmpty;
        
        $signal->stop();
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $_) {
            $this->isEmpty = !$this->isEmpty;
            break;
        }
        
        yield;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->isEmpty);
    }
}