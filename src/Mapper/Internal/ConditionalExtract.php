<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\FilterAND;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\FilterOR;
use FiiSoft\Jackdaw\Mapper\Mapper;

final class ConditionalExtract extends StateMapper
{
    private Filter $filter;
    
    private int $mode;
    private bool $negate;
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function __construct($filter, ?int $mode = null, bool $negate = false)
    {
        $this->filter = Filters::getAdapter($filter, $mode);
        $this->mode = $this->filter->getMode();
        $this->negate = $negate;
    }
    
    /**
     * @param mixed $value
     * @param mixed $key
     * @return array<string|int, mixed>
     */
    public function map($value, $key = null): array
    {
        $result = [];
        
        foreach ($value as $k => $v) {
            if ($this->negate XOR $this->filter->isAllowed($v, $k)) {
                $result[$k] = $v;
            }
        }
        
        return $result;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $result = [];
         
            foreach ($value as $k => $v) {
                if ($this->negate XOR $this->filter->isAllowed($v, $k)) {
                    $result[$k] = $v;
                }
            }
            
            yield $key => $result;
        }
    }
    
    public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof self && $other->mode === $this->mode && $other->negate === $this->negate) {
            
            if ($this->negate) {
                if ($this->filter instanceof FilterOR) {
                    $this->filter->add($other->filter);
                } else {
                    $this->filter = Filters::OR($this->filter, $other->filter);
                }
            } elseif ($this->filter instanceof FilterAND) {
                $this->filter->add($other->filter);
            } else {
                $this->filter = Filters::AND($this->filter, $other->filter);
            }
    
            return true;
        }
        
        return false;
    }
}