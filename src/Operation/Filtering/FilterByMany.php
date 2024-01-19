<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Internal\FilterByData;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class FilterByMany extends BaseOperation
{
    /** @var FilterByData[] */
    private array $checks = [];
    
    public function __construct(FilterBy $first, ?FilterBy $second = null)
    {
        $this->add($first);
        
        if ($second !== null) {
            $this->add($second);
        }
    }
    
    public function handle(Signal $signal): void
    {
        foreach ($this->checks as $check) {
            if ($check->negation === $check->filter->isAllowed(
                $signal->item->value[$check->field],
                $signal->item->key
            )) {
                return;
            }
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            foreach ($this->checks as $check) {
                if ($check->negation === $check->filter->isAllowed($value[$check->field], $key)) {
                    continue 2;
                }
            }
            
            yield $key => $value;
        }
    }
    
    public function add(FilterBy $filter): void
    {
        $this->checks[] = $filter->filterByData();
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->checks = [];
            
            parent::destroy();
        }
    }
}