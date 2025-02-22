<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpAND;

final class FilterAND extends BaseAND implements LogicAND
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->isAllowed($value, $key)) {
                continue;
            }
            
            return false;
        }
        
        return true;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($this->filters as $filter) {
            $stream = $filter->buildStream($stream);
        }
     
        return $stream;
    }
}