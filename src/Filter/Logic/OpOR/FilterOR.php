<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpOR;

final class FilterOR extends BaseOR implements LogicOR
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->isAllowed($value, $key)) {
                return true;
            }
        }
    
        return false;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            foreach ($this->filters as $filter) {
                if ($filter->isAllowed($value, $key)) {
                    yield $key => $value;
                    continue 2;
                }
            }
        }
    }
}