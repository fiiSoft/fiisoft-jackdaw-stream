<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\Buffer;

use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\EntryBufferFactory;
use FiiSoft\Jackdaw\Memo\Entry;

final class CircularEntryBufferNotFull extends CircularEntryBuffer
{
    /**
     * @inheritDoc
     */
    public function hold($key, $value): void
    {
        $this->buffer[$this->index] = new Entry($key, $value);
        
        if (++$this->index === $this->size) {
            $this->client->setEntryBuffer(EntryBufferFactory::full($this->client, $this->buffer));
            $this->client = null;
        }
    }
    
    public function get(int $index): Entry
    {
        return $index < 0 ? $this->buffer[$index + $this->index] : $this->buffer[$index];
    }
    
    public function remove(int $index): Entry
    {
        if ($index < 0) {
            $index += $this->index;
        }
        
        $removed = $this->buffer[$index];
        
        for ($i = $index + 1; $i < $this->index; ++$i) {
            $this->buffer[$i - 1] = $this->buffer[$i];
        }
        
        --$this->index;
        
        return $removed;
    }
    
    /**
     * @inheritDoc
     */
    public function getIterator(): \Generator
    {
        for ($key = 0; $key < $this->index; ++$key) {
            /* @var $entry Entry */
            $entry = $this->buffer[$key];
            
            yield $entry->key => $entry->value;
        }
    }
    
    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->index;
    }
}