<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Mapper\Mapper;

abstract class BaseMapper implements Mapper
{
    protected bool $isValueMapper = true;
    
    /**
     * @inheritDoc
     */
    public function mergeWith(Mapper $other): bool
    {
        return false;
    }
    
    /**
     * @inheritDoc
     */
    public function equals(Mapper $other): bool
    {
        return $other === $this || $other instanceof static && $other->isStateless() && $this->isStateless();
    }
    
    abstract protected function isStateless(): bool;
    
    /**
     * @inheritDoc
     */
    final public function makeKeyMapper(): Mapper
    {
        $copy = clone $this;
        $copy->isValueMapper = false;
        
        return $copy;
    }
    
    /**
     * @inheritDoc
     */
    final public function buildStream(iterable $stream): iterable
    {
        return $this->isValueMapper ? $this->buildValueMapper($stream) : $this->buildKeyMapper($stream);
    }
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => $this->map($value, $key);
        }
    }
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    protected function buildKeyMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $this->map($value, $key) => $value;
        }
    }
}