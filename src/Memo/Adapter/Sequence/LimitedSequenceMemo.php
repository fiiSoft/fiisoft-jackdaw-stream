<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\EntryBuffer;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\EntryBufferFactory;
use FiiSoft\Jackdaw\Memo\Entry;

final class LimitedSequenceMemo extends BaseSequenceMemo
{
    private EntryBuffer $buffer;
    
    private int $length;
    
    public function __construct(int $length)
    {
        if ($length < 1) {
            throw InvalidParamException::describe('length', $length);
        }
        
        $this->length = $length;
        $this->buffer = EntryBufferFactory::initial($this, $length);
    }
    
    /**
     * @inheritDoc
     */
    public function write($value, $key): void
    {
        $this->buffer->hold($key, $value);
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
        return $this->buffer->remove($index);
    }
    
    public function clear(): void
    {
        $this->buffer->clear();
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
        $this->buffer->destroy();
        $this->length = 1;
    }
}