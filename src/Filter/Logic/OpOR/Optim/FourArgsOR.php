<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpOR\Optim;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;

class FourArgsOR extends ThreeArgsOR
{
    protected Filter $fourth;
    
    /**
     * @param FilterReady|callable|mixed $first
     * @param FilterReady|callable|mixed $second
     * @param FilterReady|callable|mixed $third
     * @param FilterReady|callable|mixed $fourth
     */
    public function __construct($first, $second, $third, $fourth, ?int $mode = null)
    {
        parent::__construct($first, $second, $third, $mode);
        
        $this->fourth = Filters::getAdapter($fourth, $mode);
    }
    
    public function isAllowed($value, $key = null): bool
    {
        return $this->first->isAllowed($value, $key)
            || $this->second->isAllowed($value, $key)
            || $this->third->isAllowed($value, $key)
            || $this->fourth->isAllowed($value, $key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->first->isAllowed($value, $key)
                || $this->second->isAllowed($value, $key)
                || $this->third->isAllowed($value, $key)
                || $this->fourth->isAllowed($value, $key)
            ) {
                yield $key => $value;
            }
        }
    }
    
    /**
     * @return Filter[]
     */
    public function getFilters(): array
    {
        $filters = parent::getFilters();
        $filters[] = $this->fourth;
        
        return $filters;
    }
}