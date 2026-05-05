<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special\CollectInArray;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Special\CollectInArray;

final class InArrayKeepKeys extends CollectInArray
{
    public function handle(Signal $signal): void
    {
        $this->result[$signal->item->key] = $signal->item->value;
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->result[$key] = $value;
        }
        
        yield;
    }
    
    /**
     * @inheritDoc
     */
    public function setCollectedItems(array $items): void
    {
        $this->result = [];
        
        foreach ($items as $item) {
            $this->result[$item->key] = $item->value;
        }
    }
    
    public function setCollectedData(array $data): void
    {
        $this->result = $data;
    }
}