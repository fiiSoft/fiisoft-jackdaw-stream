<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Adapter;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;
use FiiSoft\Jackdaw\Filter\Filter;

final class FilterAdapter extends BaseAdapter
{
    private Filter $filter;
    
    public function __construct(Filter $filter)
    {
        $this->filter = $filter->checkValue();
    }
    
    /**
     * @inheritDoc
     */
    public function compare($value1, $value2): int
    {
        return $this->filter->isAllowed($value1) <=> $this->filter->isAllowed($value2);
    }
    
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
}