<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\EntryBuffer;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\EntryBufferFactory;
use FiiSoft\Jackdaw\Memo\Entry;
use FiiSoft\Jackdaw\Memo\Sequence\Matcher\SequenceMatcherPredicate;

final class LimitedSequenceMemo extends BaseSequenceMemo
{
    private EntryBuffer $buffer;
    
    private int $length;
    
    /** @var SequenceMatcherPredicate[] */
    private array $observers = [];
    
    public function __construct(int $length)
    {
        if ($length < 1) {
            throw InvalidParamException::describe('length', $length);
        }
        
        $this->length = $length;
        $this->initBuffer();
    }
    
    /**
     * @inheritDoc
     */
    public function write($value, $key): void
    {
        $this->buffer->hold($key, $value);
        
        foreach ($this->observers as $observer) {
            $observer->entryAdded($value, $key);
        }
    }
    
    public function get(int $index): Entry
    {
        return $this->buffer->get($index);
    }
    
    /**
     * @inheritDoc
     */
    public function valueOf(int $index)
    {
        return $this->buffer->get($index)->value;
    }
    
    /**
     * @inheritDoc
     */
    public function keyOf(int $index)
    {
        return $this->buffer->get($index)->key;
    }
    
    public function isFull(): bool
    {
        return $this->buffer->count() === $this->length;
    }
    
    public function isEmpty(): bool
    {
        return $this->buffer->count() === 0;
    }
    
    public function remove(int $index): Entry
    {
        $removed = $this->buffer->remove($index);
        
        if ($index < 0) {
            $index += $this->buffer->count();
        }
        
        foreach ($this->observers as $observer) {
            $observer->entryRemoved($index);
        }
        
        return $removed;
    }
    
    public function clear(): void
    {
        $this->buffer->clear();
        
        foreach ($this->observers as $observer) {
            $observer->sequenceCleared();
        }
    }
    
    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->buffer->fetchData();
    }
    
    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        return $this->buffer->fetchValues();
    }
    
    /**
     * @inheritDoc
     */
    public function getKeys(): array
    {
        return $this->buffer->fetchKeys();
    }
    
    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->buffer->count();
    }
    
    /**
     * @inheritDoc
     */
    public function fold($initial, callable $reducer)
    {
        $acc = $initial;
        
        foreach ($this->buffer->getIterator() as $key => $value) {
            $acc = $reducer($acc, $value, $key);
        }
        
        return $acc;
    }
    
    /**
     * @inheritDoc
     */
    public function reduce(callable $reducer)
    {
        $isFirst = true;
        $acc = null;
        
        foreach ($this->buffer->getIterator() as $value) {
            if ($isFirst) {
                $acc = $value;
                $isFirst = false;
            } else {
                $acc = $reducer($acc, $value);
            }
        }
        
        return $acc;
    }
    
    public function register(SequenceMatcherPredicate $observer): void
    {
        $this->observers[\spl_object_id($observer)] = $observer;
    }
    
    /**
     * @inheritDoc
     */
    public function getIterator(): \Iterator
    {
        yield from $this->buffer->getIterator();
    }
    
    public function setEntryBuffer(EntryBuffer $buffer): void
    {
        $this->buffer = $buffer;
    }
    
    public function destroy(): void
    {
        $this->clear();
        $this->buffer->destroy();
        $this->length = 1;
    }
    
    public function __clone()
    {
        $this->initBuffer();
    }
    
    private function initBuffer(): void
    {
        $this->buffer = EntryBufferFactory::initial($this, $this->length);
    }
}