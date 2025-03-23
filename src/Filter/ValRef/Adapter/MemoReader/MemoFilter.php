<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Adapter\MemoReader;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\ValRef\Adapter\BaseFilterAdapter;
use FiiSoft\Jackdaw\Memo\MemoReader;

final class MemoFilter extends BaseFilterAdapter
{
    private MemoReader $reader;
    
    public function __construct(MemoReader $reader, Filter $filter)
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
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed($this->reader->read())) {
                yield $key => $value;
            }
        }
    }
    
    public function negate(): Filter
    {
        return new self($this->reader, $this->filter->negate());
    }
    
    public function equals(Filter $other): bool
    {
        return $other === $this || $other instanceof $this
            && $other->reader->equals($this->reader)
            && parent::equals($other);
    }
    
    protected function createFilter(Filter $filter): Filter
    {
        return new self($this->reader, $filter);
    }
}