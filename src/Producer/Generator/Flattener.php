<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Producer\Generator\Exception\GeneratorExceptionFactory;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class Flattener extends BaseProducer
{
    public const MAX_LEVEL = 128;
    
    /** @var iterable<string|int, mixed> */
    private iterable $iterable = [];
    
    private int $maxLevel;
    
    /**
     * @param iterable<string|int, mixed> $iterable
     * @param int $level 0 means no nesting restrictions (well, almost)
     */
    public function __construct(iterable $iterable = [], int $level = 0)
    {
        $this->setLevel($level)->setIterable($iterable);
    }
    
    public function getIterator(): \Generator
    {
        yield from $this->iterate($this->iterable, 1);
    }
    
    /**
     * @param iterable<string|int, mixed> $values
     */
    private function iterate(iterable $values, int $level): \Generator
    {
        if ($level < $this->maxLevel) {
            foreach ($values as $key => $value) {
                if (\is_iterable($value)) {
                    yield from $this->iterate($value, $level + 1);
                } else {
                    yield $key => $value;
                }
            }
        } else {
            yield from $values;
        }
    }
    
    /**
     * @param int $level
     * @return $this fluent interface
     */
    public function setLevel(int $level): self
    {
        if ($level < 0 || $level > self::MAX_LEVEL) {
            throw GeneratorExceptionFactory::invalidParamLevel($level, self::MAX_LEVEL);
        }
    
        $this->maxLevel = $level ?: self::MAX_LEVEL;
        
        return $this;
    }
    
    /**
     * @param iterable<string|int, mixed> $iterable date to flatten
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
            throw GeneratorExceptionFactory::invalidParamLevel($level, self::MAX_LEVEL);
        }
    
        $this->maxLevel = \min($this->maxLevel + $level, self::MAX_LEVEL);
    }
    
    public function decreaseLevel(): void
    {
        if ($this->isLevel(0) || $this->isLevel(1)) {
            throw GeneratorExceptionFactory::cannotDecreaseLevel();
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
    
    public function destroy(): void
    {
        $this->iterable = [];
        
        parent::destroy();
    }
}