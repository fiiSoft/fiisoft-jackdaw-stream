<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\SimpleFinalOperation;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Stream;

final class IsEmpty extends SimpleFinalOperation
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
    
    public function hasResult(): bool
    {
        return true;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->isEmpty);
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        foreach ($producer->feed($signal->item) as $_) {
            $this->handle($signal);
            break;
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($data as $item->key => $item->value) {
            $this->handle($signal);
            break;
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        foreach ($items as $item) {
            $signal->item->key = $item->key;
            $signal->item->value = $item->value;
            
            $this->handle($signal);
            break;
        }
        
        return $this->streamingFinished($signal);
    }
}