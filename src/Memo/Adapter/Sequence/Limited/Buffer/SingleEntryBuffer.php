<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\Buffer;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\EntryBuffer;
use FiiSoft\Jackdaw\Memo\Entry;

final class SingleEntryBuffer implements EntryBuffer
{
    private ?Entry $entry = null;
    
    /**
     * @inheritDoc
     */
    public function hold($key, $value): void
    {
        if ($this->entry === null) {
            $this->entry = new Entry($key, $value);
        } else {
            $this->entry->key = $key;
            $this->entry->value = $value;
        }
    }
    
    public function get(int $index): Entry
    {
        if ($index === 0 || $index === -1) {
            return $this->entry;
        }
        
        throw InvalidParamException::describe('index', $index);
    }
    
    public function remove(int $index): Entry
    {
        if ($index === 0 || $index === -1) {
            $removed = $this->entry;
            $this->entry = null;
            
            return $removed;
        }
        
        throw InvalidParamException::describe('index', $index);
    }
    
    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->entry === null ? 0 : 1;
    }
    
    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->entry = null;
    }
    
    /**
     * @inheritDoc
     */
    public function fetchData(bool $reindex = false, int $skip = 0): array
    {
        return $this->entry === null ? [] : [$this->entry->key => $this->entry->value];
    }
    
    public function fetchValues(): array
    {
        return $this->entry === null ? [] : [$this->entry->value];
    }
    
    public function fetchKeys(): array
    {
        return $this->entry === null ? [] : [$this->entry->key];
    }
    
    /**
     * @inheritDoc
     */
    public function getIterator(): \Generator
    {
        yield from $this->fetchData();
    }
    
    public function destroy(): void
    {
        $this->clear();
    }
}