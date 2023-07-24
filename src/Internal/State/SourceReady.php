<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Stream;

final class SourceReady extends Source
{
    public function __construct(
        bool $isLoop,
        Stream $stream,
        Producer $producer,
        Signal $signal,
        Pipe $pipe,
        Stack $stack,
        \Generator $currentSource
    ) {
        parent::__construct($isLoop, $stream, $producer, $signal, $pipe, $stack);
        
        $this->currentSource = $currentSource;
    }
    
    public function setNextValue(Item $item): void
    {
        $this->currentSource->send($item);
    }
    
    public function hasNextItem(): bool
    {
        $this->currentSource->next();
        
        return $this->currentSource->valid();
    }
}