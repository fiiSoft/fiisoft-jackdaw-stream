<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence;

use FiiSoft\Jackdaw\Memo\Entry;

final class InfiniteSequenceMemo extends BaseSequenceMemo
{
    /** @var Entry[] */
    private array $entries = [];
    
    /**
     * @inheritDoc
     */
    public function write($value, $key): void
    {
        $this->entries[] = new Entry($key, $value);
    }
    
    public function get(int $index): Entry
    {
        return $index >= 0 ? $this->entries[$index] : $this->entries[$index + \count($this->entries)];
    }
    
    public function count(): int
    {
        return \count($this->entries);
    }
    
    public function isEmpty(): bool
    {
        return empty($this->entries);
    }
    
    public function isFull(): bool
    {
        return false;
    }
    
    /**
     * @inheritDoc
     */
    public function valueOf(int $index)
    {
        return $this->get($index)->value;
    }
    
    /**
     * @inheritDoc
     */
    public function keyOf(int $index)
    {
        return $this->get($index)->key;
    }
    
    public function remove(int $index): Entry
    {
        if ($index < 0) {
            $index += \count($this->entries);
        }
        
        $removed = $this->entries[$index];
        unset($this->entries[$index]);
        
        if ($index < \count($this->entries)) {
            $this->entries = \array_values($this->entries);
        }
        
        return $removed;
    }
    
    /**
     * @inheritDoc
     */
    public function fold($initial, callable $reducer)
    {
        $acc = $initial;
        
        foreach ($this->entries as $entry) {
            $acc = $reducer($acc, $entry->value, $entry->key);
        }
        
        return $acc;
    }
    
    /**
     * @inheritDoc
     */
    public function reduce(callable $reducer)
    {
        $acc = $this->entries[0]->value;
        
        for ($i = 1, $max = \count($this->entries); $i < $max; ++$i) {
            $acc = $reducer($acc, $this->entries[$i]->value);
        }
        
        return $acc;
    }
    
    public function clear(): void
    {
        $this->entries = [];
    }
    
    public function toArray(): array
    {
        $result = [];
        
        foreach ($this->entries as $entry) {
            $result[$entry->key] = $entry->value;
        }
        
        return $result;
    }
    
    public function getIterator(): \Traversable
    {
        return (function () {
            foreach ($this->entries as $entry) {
                yield $entry->key => $entry->value;
            }
        })();
    }
    
    public function destroy(): void
    {
        $this->clear();
    }
}