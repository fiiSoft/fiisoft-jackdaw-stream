<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class Accumulate extends BaseOperation
{
    private Filter $filter;
    
    private bool $preserveKeys;
    private bool $reverse;
    private int $mode;
    
    private int $index = 0;
    private array $data = [];
    
    /**
     * @param Filter|Predicate|callable|mixed $filter
     * @param int $mode
     * @param bool $preserveKeys
     * @param bool $reverse
     */
    public function __construct($filter, int $mode = Check::VALUE, bool $preserveKeys = false, bool $reverse = false)
    {
        $this->filter = Filters::getAdapter($filter);
        $this->mode = Check::getMode($mode);
        $this->preserveKeys = $preserveKeys;
        $this->reverse = $reverse;
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if ($this->reverse XOR $this->filter->isAllowed($item->value, $item->key, $this->mode)) {
            if ($this->preserveKeys) {
                $this->data[$item->key] = $item->value;
            } else {
                $this->data[] = $item->value;
            }
        } elseif (!empty($this->data)) {
            $item->key = $this->index++;
            $item->value = $this->data;
            $this->data = [];

            $this->next->handle($signal);
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty && !empty($this->data)) {
            $signal->resume();
            
            $signal->item->key = $this->index++;
            $signal->item->value = $this->data;
            $this->data = [];
            
            $this->next->handle($signal);
            
            return true;
        }
        
        return parent::streamingFinished($signal);
    }
}