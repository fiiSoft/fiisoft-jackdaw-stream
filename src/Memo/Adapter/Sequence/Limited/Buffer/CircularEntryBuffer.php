<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\Buffer;

use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\EntryBuffer;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\EntryBufferFactory;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\LimitedSequenceMemo;
use FiiSoft\Jackdaw\Memo\Entry;

abstract class CircularEntryBuffer implements EntryBuffer
{
    protected ?LimitedSequenceMemo $client = null;
    
    /** @var \SplFixedArray<Entry> */
    protected \SplFixedArray $buffer;
    
    protected int $size, $index;
    
    /**
     * @param \SplFixedArray<Entry> $buffer
     */
    public function __construct(LimitedSequenceMemo $client, \SplFixedArray $buffer, int $index = 0)
    {
        $this->client = $client;
        $this->buffer = $buffer;
        $this->index = $index;
        $this->size = $buffer->getSize();
    }
    
    /**
     * @inheritDoc
     */
    final public function fetchData(): array
    {
        $result = [];
        $key = $this->index;
        $count = $this->count();
        
        for ($i = 0; $i < $count; ++$i) {
            if ($key === $count) {
                $key = 0;
            }
            
            $x = $this->buffer[$key++];
            $result[$x->key] = $x->value;
        }
        
        return $result;
    }
    
    /**
     * @inheritDoc
     */
    final public function fetchValues(): array
    {
        $result = [];
        $key = $this->index;
        $count = $this->count();
        
        for ($i = 0; $i < $count; ++$i) {
            if ($key === $count) {
                $key = 0;
            }
            
            $result[] = $this->buffer[$key++]->value;
        }
        
        return $result;
    }
    
    /**
     * @inheritDoc
     */
    final public function fetchKeys(): array
    {
        $result = [];
        $key = $this->index;
        $count = $this->count();
        
        for ($i = 0; $i < $count; ++$i) {
            if ($key === $count) {
                $key = 0;
            }
            
            $result[] = $this->buffer[$key++]->key;
        }
        
        return $result;
    }
    
    final public function clear(): void
    {
        $this->client->setEntryBuffer(EntryBufferFactory::initial($this->client, $this->size));
        $this->client = null;
    }
    
    final public function destroy(): void
    {
        $this->size = 0;
        $this->index = 0;
        $this->client = null;
        $this->buffer->setSize(0);
    }
}