<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence;

use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader\KeyReader;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader\PairReader;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader\TupleReader;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader\ValueReader;
use FiiSoft\Jackdaw\Memo\Entry;
use FiiSoft\Jackdaw\Memo\MemoReader;
use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Stream;

abstract class BaseSequenceMemo implements SequenceMemo
{
    protected SequenceEntries $sequence;
    
    /** @var MemoReader[] */
    private array $keyReaders = [];
    
    /** @var MemoReader[] */
    private array $valueReaders = [];
    
    /** @var MemoReader[] */
    private array $tupleReaders = [];
    
    /** @var MemoReader[] */
    private array $pairReaders = [];
    
    public function __construct()
    {
        $this->sequence = new SequenceEntries();
    }
    
    final public function count(): int
    {
        return \count($this->sequence->entries);
    }
    
    final public function isEmpty(): bool
    {
        return empty($this->sequence->entries);
    }
    
    final public function get(int $index): Entry
    {
        return $this->sequence->entries[$this->adjust($index)];
    }
    
    final public function remove(int $index): Entry
    {
        $index = $this->adjust($index);
        
        $removed = $this->sequence->entries[$index];
        unset($this->sequence->entries[$index]);
        
        if ($index < \count($this->sequence->entries)) {
            $this->sequence->entries = \array_values($this->sequence->entries);
        }
        
        return $removed;
    }
    
    final public function clear(): void
    {
        $this->sequence->entries = [];
    }
    
    final public function toArray(): array
    {
        $result = [];
        
        foreach ($this->sequence->entries as $entry) {
            $result[$entry->key] = $entry->value;
        }
        
        return $result;
    }
    
    final public function key(int $index): MemoReader
    {
        $index = $this->adjust($index);
        
        if (!isset($this->keyReaders[$index])) {
            $this->keyReaders[$index] = new KeyReader($this->sequence, $index);
        }
        
        return $this->keyReaders[$index];
    }
    
    final public function value(int $index): MemoReader
    {
        $index = $this->adjust($index);
        
        if (!isset($this->valueReaders[$index])) {
            $this->valueReaders[$index] = new ValueReader($this->sequence, $index);
        }
        
        return $this->valueReaders[$index];
    }
    
    final public function tuple(int $index): MemoReader
    {
        $index = $this->adjust($index);
        
        if (!isset($this->tupleReaders[$index])) {
            $this->tupleReaders[$index] = new TupleReader($this->sequence, $index);
        }
        
        return $this->tupleReaders[$index];
    }
    
    final public function pair(int $index): MemoReader
    {
        $index = $this->adjust($index);
        
        if (!isset($this->pairReaders[$index])) {
            $this->pairReaders[$index] = new PairReader($this->sequence, $index);
        }
        
        return $this->pairReaders[$index];
    }
    
    /**
     * @inheritDoc
     */
    final public function valueOf(int $index)
    {
        return $this->sequence->entries[$this->adjust($index)]->value;
    }
    
    /**
     * @inheritDoc
     */
    final public function keyOf(int $index)
    {
        return $this->sequence->entries[$this->adjust($index)]->key;
    }
    
    private function adjust(int $index): int
    {
        return $index < 0 ? $index + \count($this->sequence->entries) : $index;
    }
    
    /**
     * @inheritDoc
     */
    final public function fold($initial, callable $reducer)
    {
        $acc = $initial;
        
        foreach ($this->sequence->entries as $entry) {
            $acc = $reducer($acc, $entry->value, $entry->key);
        }
        
        return $acc;
    }
    
    /**
     * @inheritDoc
     */
    final public function reduce(callable $reducer)
    {
        $acc = $this->sequence->entries[0]->value;
        
        for ($i = 1, $max = \count($this->sequence->entries); $i < $max; ++$i) {
            $acc = $reducer($acc, $this->sequence->entries[$i]->value);
        }
        
        return $acc;
    }
    
    final public function getIterator(): \Traversable
    {
        return (function () {
            foreach ($this->sequence->entries as $entry) {
                yield $entry->key => $entry->value;
            }
        })();
    }
    
    final public function stream(): Stream
    {
        return Stream::from($this);
    }
}