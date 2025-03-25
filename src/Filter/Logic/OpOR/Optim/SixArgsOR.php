<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpOR\Optim;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;

class SixArgsOR extends FiveArgsOR
{
    protected Filter $sixth;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $first
     * @param FilterReady|callable|array<string|int, mixed>|scalar $second
     * @param FilterReady|callable|array<string|int, mixed>|scalar $third
     * @param FilterReady|callable|array<string|int, mixed>|scalar $fourth
     * @param FilterReady|callable|array<string|int, mixed>|scalar $fifth
     * @param FilterReady|callable|array<string|int, mixed>|scalar $sixth
     */
    public function __construct($first, $second, $third, $fourth, $fifth, $sixth, ?int $mode = null)
    {
        parent::__construct($first, $second, $third, $fourth, $fifth, $mode);
        
        $this->sixth = Filters::getAdapter($sixth, $mode);
    }
    
    public function isAllowed($value, $key = null): bool
    {
        return $this->first->isAllowed($value, $key)
            || $this->second->isAllowed($value, $key)
            || $this->third->isAllowed($value, $key)
            || $this->fourth->isAllowed($value, $key)
            || $this->fifth->isAllowed($value, $key)
            || $this->sixth->isAllowed($value, $key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->first->isAllowed($value, $key)
                || $this->second->isAllowed($value, $key)
                || $this->third->isAllowed($value, $key)
                || $this->fourth->isAllowed($value, $key)
                || $this->fifth->isAllowed($value, $key)
                || $this->sixth->isAllowed($value, $key)
            ) {
                yield $key => $value;
            }
        }
    }
    
    /**
     * @inheritDoc
     */
    protected function collectFilters(): array
    {
        $filters = parent::collectFilters();
        $filters[] = $this->sixth;
        
        return $filters;
    }
}