<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\BaseFilter;

abstract class StringFilter extends BaseFilter
{
    protected bool $ignoreCase;
    
    protected function __construct(int $mode, bool $ignoreCase)
    {
        parent::__construct($mode);
        
        $this->ignoreCase = $ignoreCase;
    }
    
    /**
     * @return self new instance
     */
    public function ignoreCase(): self
    {
        $copy = clone $this;
        $copy->ignoreCase = true;
        
        return $copy;
    }
    
    /**
     * @return self new instance
     */
    public function caseSensitive(): self
    {
        $copy = clone $this;
        $copy->ignoreCase = false;
        
        return $copy;
    }
    
    /**
     * @inheritDoc
     */
    final public function buildStream(iterable $stream): iterable
    {
        return $this->ignoreCase ? $this->compareCaseInsensitive($stream) : $this->compareCaseSensitive($stream);
    }
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    abstract protected function compareCaseInsensitive(iterable $stream): iterable;
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    abstract protected function compareCaseSensitive(iterable $stream): iterable;
    
    abstract public function negate(): StringFilter;
}