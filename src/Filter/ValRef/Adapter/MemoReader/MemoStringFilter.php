<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Adapter\MemoReader;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\String\StringFilter;
use FiiSoft\Jackdaw\Filter\ValRef\Adapter\BaseStringFilterAdapter;
use FiiSoft\Jackdaw\Memo\MemoReader;

final class MemoStringFilter extends BaseStringFilterAdapter
{
    private MemoReader $reader;
    
    public function __construct(MemoReader $reader, StringFilter $filter)
    {
        parent::__construct($filter);
        
        $this->reader = $reader;
    }
    
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->filter->isAllowed($this->reader->read());
    }
    
    /**
     * @inheritDoc
     */
    protected function iterateStream(StringFilter $filter, iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($filter->isAllowed($this->reader->read())) {
                yield $key => $value;
            }
        }
    }
    
    public function negate(): self
    {
        return new self($this->reader, $this->filter->negate());
    }
    
    public function equals(Filter $other): bool
    {
        return $other === $this || $other instanceof $this
            && $other->reader->equals($this->reader)
            && parent::equals($other);
    }
}