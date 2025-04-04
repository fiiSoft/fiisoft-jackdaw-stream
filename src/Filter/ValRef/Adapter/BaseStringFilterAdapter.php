<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Adapter;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\String\AbstractStringFilter;
use FiiSoft\Jackdaw\Filter\String\StringFilter;
use FiiSoft\Jackdaw\Internal\Check;

abstract class BaseStringFilterAdapter extends AbstractStringFilter
{
    protected StringFilter $filter;
    
    public function __construct(StringFilter $filter)
    {
        parent::__construct(Check::VALUE, $filter->isCaseInsensitive());
        
        $this->filter = $filter->checkValue();
    }
    
    final public function inMode(?int $mode): self
    {
        return $this;
    }
    
    /**
     * @return static
     */
    final public function ignoreCase(): StringFilter
    {
        if ($this->ignoreCase) {
            return $this;
        }
        
        $copy = parent::ignoreCase();
        $copy->filter = $copy->filter->ignoreCase();
        
        return $copy;
    }
    
    /**
     * @return static
     */
    final public function caseSensitive(): StringFilter
    {
        if ($this->ignoreCase) {
            $copy = parent::caseSensitive();
            $copy->filter = $copy->filter->caseSensitive();
            
            return $copy;
        }
        
        return $this;
    }
    
    final protected function compareCaseInsensitive(iterable $stream): iterable
    {
        return $this->iterateStream(
            $this->filter->isCaseInsensitive() ? $this->filter : $this->filter->ignoreCase(),
            $stream
        );
    }
    
    final protected function compareCaseSensitive(iterable $stream): iterable
    {
        return $this->iterateStream(
            $this->filter->isCaseInsensitive() ? $this->filter->caseSensitive() : $this->filter,
            $stream
        );
    }
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    abstract protected function iterateStream(StringFilter $filter, iterable $stream): iterable;
    
    public function equals(Filter $other): bool
    {
        return $other === $this || $other instanceof $this
            && $other->filter->equals($this->filter)
            && parent::equals($other);
    }
}