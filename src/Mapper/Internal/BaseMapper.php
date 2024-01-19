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
        return $this === $other
            || (
                $this instanceof $other
                && $this->isStateless()
                && $other instanceof BaseMapper
                && $other->isStateless()
            );
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
    
    final public function buildStream(iterable $stream): iterable
    {
        return $this->isValueMapper ? $this->buildValueMapper($stream) : $this->buildKeyMapper($stream);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => $this->map($value, $key);
        }
    }
    
    protected function buildKeyMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $this->map($value, $key) => $value;
        }
    }
}