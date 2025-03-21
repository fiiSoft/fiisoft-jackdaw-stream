<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\ItemBuffer;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;

abstract class CircularItemBuffer implements ItemBuffer
{
    protected ?ItemBufferClient $client = null;
    
    /** @var \SplFixedArray<Item> */
    protected \SplFixedArray $buffer;
    
    protected int $size;
    protected int $index = 0;
    
    public static function initial(ItemBufferClient $client, int $size): ItemBuffer
    {
        return $size === 1
            ? new SingleItemBuffer()
            : new CircularItemBufferNotFull($client, new \SplFixedArray($size));
    }
    
    /**
     * @param \SplFixedArray<Item> $buffer
     */
    protected static function full(ItemBufferClient $client, \SplFixedArray $buffer): self
    {
        return new CircularItemBufferFull($client, $buffer);
    }
    
    /**
     * @param \SplFixedArray<Item> $buffer
     */
    private function __construct(ItemBufferClient $client, \SplFixedArray $buffer)
    {
        $this->client = $client;
        $this->buffer = $buffer;
        $this->size = $buffer->getSize();
    }
    
    /**
     * @inheritDoc
     */
    final public function fetchData(bool $reindex = false, int $skip = 0): array
    {
        $result = [];
        $key = $this->index;
        $count = $this->count();
        
        for ($i = 0; $i < $count; ++$i) {
            if ($key === $count) {
                $key = 0;
            }
            
            $x = $this->buffer[$key++];
            if ($skip > 0) {
                --$skip;
                continue;
            }
            
            if ($reindex) {
                $result[] = $x->value;
            } else {
                $result[$x->key] = $x->value;
            }
        }
        
        return $result;
    }
    
    final public function getLength(): int
    {
        return $this->size;
    }
    
    final public function createProducer(): Producer
    {
        return new CircularBufferIterator($this->buffer, $this->count(), $this->index);
    }
    
    final public function clear(): void
    {
        $this->client->setItemBuffer(self::initial($this->client, $this->size));
        $this->destroy();
    }
    
    final public function destroy(): void
    {
        $this->size = 0;
        $this->index = 0;
        $this->client = null;
        $this->buffer->setSize(0);
    }
}