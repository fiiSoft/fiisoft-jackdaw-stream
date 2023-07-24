<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\NonCountableProducer;

final class Flattener extends NonCountableProducer
{
    public const MAX_LEVEL = 128;
    
    private iterable $iterable = [];
    private int $maxLevel;
    
    private Item $item;
    
    /**
     * @param int $level 0 means no nesting restrictions (well, almost)
     */
    public function __construct(iterable $iterable = [], int $level = 0)
    {
        $this->setLevel($level)->setIterable($iterable);
    }
    
    public function feed(Item $item): \Generator
    {
        $this->item = $item;
        
        yield from $this->iterate($this->iterable, 1);
    }
    
    private function iterate(iterable $values, int $level): \Generator
    {
        if ($level < $this->maxLevel) {
            foreach ($values as $this->item->key => $this->item->value) {
                if (\is_iterable($this->item->value)) {
                    yield from $this->iterate($this->item->value, $level + 1);
                } else {
                    yield;
                }
            }
        } else {
            foreach ($values as $this->item->key => $this->item->value) {
                yield;
            }
        }
    }
    
    /**
     * @param int $level
     * @return $this fluent interface
     */
    public function setLevel(int $level): self
    {
        if ($level < 0 || $level > self::MAX_LEVEL) {
            throw new \InvalidArgumentException('Invalid param level, must be 0...'.self::MAX_LEVEL);
        }
    
        $this->maxLevel = $level ?: self::MAX_LEVEL;
        
        return $this;
    }
    
    /**
     * @param iterable $iterable date to flatten
     * @return $this fluent interface
     */
    public function setIterable(iterable $iterable): self
    {
        $this->iterable = $iterable;
        
        return $this;
    }
    
    public function increaseLevel(int $level): void
    {
        if ($level < 0) {
            throw new \InvalidArgumentException('Invalid param level, must be greater than 0');
        }
    
        $this->maxLevel = \min($this->maxLevel + $level, self::MAX_LEVEL);
    }
    
    public function decreaseLevel(): void
    {
        if ($this->isLevel(0) || $this->isLevel(1)) {
            throw new \LogicException('Cannot decrease level');
        }
        
        --$this->maxLevel;
    }
    
    public function maxLevel(): int
    {
        return $this->maxLevel;
    }
    
    public function isLevel(int $level): bool
    {
        return $level === 0 ? $this->maxLevel === self::MAX_LEVEL : $level === $this->maxLevel;
    }
}