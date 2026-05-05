<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating\Collect;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Terminating\Collect;

final class CollectReindexKeys extends Collect
{
    public function handle(Signal $signal): void
    {
        $this->collected[] = $signal->item->value;
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        return $this->isSelfStream ? $this->iteratingStream($stream) : $this->collectingStream($stream);
    }
    
    /**
     * @inheritDoc
     */
    public function setCollectedItems(array $items): void
    {
        $this->collected = [];
        
        foreach ($items as $item) {
            $this->collected[] = $item->value;
        }
    }
    
    public function setCollectedData(array $data): void
    {
        $this->collected = \array_values($data);
    }
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    private function iteratingStream(iterable $stream): iterable
    {
        $index = -1;
        
        foreach ($stream as $value) {
            $this->collected[] = $value;
            
            yield ++$index => $value;
        }
    }
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    private function collectingStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            $this->collected[] = $value;
        }
        
        yield;
    }
}