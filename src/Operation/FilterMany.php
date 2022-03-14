<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Filter\Internal\FilterData;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class FilterMany extends BaseOperation
{
    /** @var FilterData[] */
    private array $filters = [];
    
    public function __construct(Filter $first, Filter $second)
    {
        $this->add($first);
        $this->add($second);
    }
    
    public function handle(Signal $signal): void
    {
        foreach ($this->filters as $filter) {
            if ($filter->negation === $filter->filter->isAllowed(
                    $signal->item->value,
                    $signal->item->key,
                    $filter->mode
                )
            ) {
                return;
            }
        }
        
        $this->next->handle($signal);
    }
    
    public function add(Filter $filter): void
    {
        $this->filters[] = $filter->filterData();
    }
}
