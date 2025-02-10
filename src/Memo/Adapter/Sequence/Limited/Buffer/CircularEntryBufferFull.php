<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\Buffer;

use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\EntryBufferFactory;
use FiiSoft\Jackdaw\Memo\Entry;

final class CircularEntryBufferFull extends CircularEntryBuffer
{
    private Entry $current;
    
    /**
     * @inheritDoc
     */
    public function hold($key, $value): void
    {
        $this->current = $this->buffer[$this->index];
        
        $this->current->key = $key;
        $this->current->value = $value;
    
        if (++$this->index === $this->size) {
            $this->index = 0;
        }
    }
    
    public function get(int $index): Entry
    {
        $key = $index + $this->index;
        
        if ($index >= 0) {
            return $key < $this->size ? $this->buffer[$key] : $this->buffer[$key - $this->size];
        }
        
        return $key >= 0 ? $this->buffer[$key] : $this->buffer[$key + $this->size];
    }
    
    public function remove(int $index): Entry
    {
        $key = $index + $this->index;
        
        if ($index >= 0 && $key >= $this->size) {
            $key -= $this->size;
        } elseif ($key < 0) {
            $key += $this->size;
        }
        
        $removed = $this->buffer[$key];
        
        $buffer = new \SplFixedArray($this->size);
        
        for ($i = 0, $j = 0, $idx = $this->index; $i < $this->size; ++$i, ++$idx) {
            if ($idx === $this->size) {
                $idx = 0;
            }
            
            if ($idx !== $key) {
                $buffer[$j++] = $this->buffer[$idx];
            }
        }
        
        $this->client->setEntryBuffer(EntryBufferFactory::notFull($this->client, $buffer, $this->size - 1));
        $this->client = null;
        
        return $removed;
    }
    
    /**
     * @inheritDoc
     */
    public function getIterator(): \Generator
    {
        for ($i = 0, $key = $this->index; $i < $this->size; ++$i) {
            if ($key === $this->size) {
                $key = 0;
            }
            
            /* @var $entry Entry */
            $entry = $this->buffer[$key++];
            
            yield $entry->key => $entry->value;
        }
    }
    
    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->size;
    }
}