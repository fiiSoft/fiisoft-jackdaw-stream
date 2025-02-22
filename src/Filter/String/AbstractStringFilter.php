<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\AbstractLogicFilter;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class AbstractStringFilter extends AbstractLogicFilter implements StringFilter
{
    protected bool $ignoreCase;
    protected int $mode;
    
    protected function __construct(int $mode, bool $ignoreCase)
    {
        parent::__construct();
        
        $this->mode = Mode::get($mode);
        $this->ignoreCase = $ignoreCase;
    }
    
    final public function getMode(): int
    {
        return $this->mode;
    }
    
    final public function checkValue(): StringFilter
    {
        return $this->inMode(Check::VALUE);
    }
    
    final public function checkKey(): StringFilter
    {
        return $this->inMode(Check::KEY);
    }
    
    final public function checkBoth(): StringFilter
    {
        return $this->inMode(Check::BOTH);
    }
    
    final public function checkAny(): StringFilter
    {
        return $this->inMode(Check::ANY);
    }
    
    /**
     * @return static
     */
    public function ignoreCase(): StringFilter
    {
        $copy = clone $this;
        $copy->ignoreCase = true;
        
        return $copy;
    }
    
    /**
     * @return static
     */
    public function caseSensitive(): StringFilter
    {
        $copy = clone $this;
        $copy->ignoreCase = false;
        
        return $copy;
    }
    
    final public function isCaseInsensitive(): bool
    {
        return $this->ignoreCase;
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
    
    final protected function negatedMode(): ?int
    {
        return Mode::negate($this->getMode());
    }
    
    public function equals(Filter $other): bool
    {
        return $other instanceof $this
            && $other->mode === $this->mode
            && $other->ignoreCase === $this->ignoreCase;
    }
}