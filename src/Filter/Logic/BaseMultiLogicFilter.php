<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\Filter;

abstract class BaseMultiLogicFilter extends BaseLogicFilter
{
    protected ?int $mode = null;
    
    final public function getMode(): ?int
    {
        if ($this->mode !== null) {
            return $this->mode;
        }
        
        foreach ($this->getFilters() as $filter) {
            if ($this->mode === null) {
                $this->mode = $filter->getMode();
            } elseif ($this->mode !== $filter->getMode()) {
                $this->mode = null;
                break;
            }
        }
        
        return $this->mode;
    }
    
    /**
     * Helper method.
     *
     * @return Filter[]
     */
    final protected function negatedFilters(): array
    {
        return \array_map(static fn(Filter $filter): Filter => $filter->negate(), $this->getFilters());
    }
    
    final public function equals(Filter $other): bool
    {
        if ($other === $this) {
            return true;
        }
        
        if ($other instanceof static) {
            
            if ($other->getMode() !== $this->getMode()) {
                return false;
            }
            
            $theirs = $other->getFilters();
            $myFilters = $this->getFilters();
            
            if (\count($theirs) !== \count($myFilters)) {
                return false;
            }
            
            foreach ($myFilters as $mine) {
                foreach ($theirs as $key => $their) {
                    if ($mine->equals($their)) {
                        unset($theirs[$key]);
                        continue 2;
                    }
                }
                
                return false;
            }
            
            return true;
        }
        
        return false;
    }
}