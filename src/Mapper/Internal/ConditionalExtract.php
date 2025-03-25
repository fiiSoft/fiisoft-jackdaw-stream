<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Mapper\Mapper;

final class ConditionalExtract extends StateMapper
{
    private Filter $filter;
    private bool $negate;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function __construct($filter, ?int $mode = null, bool $negate = false)
    {
        $this->filter = Filters::getAdapter($filter, $mode);
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
        
        if ($this->negate) {
            foreach ($value as $k => $v) {
                if ($this->filter->isAllowed($v, $k)) {
                    continue;
                }
                
                $result[$k] = $v;
            }
        } else {
            foreach ($value as $k => $v) {
                if ($this->filter->isAllowed($v, $k)) {
                    $result[$k] = $v;
                }
            }
        }
        
        return $result;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        if ($this->negate) {
            foreach ($stream as $key => $value) {
                $result = [];
                
                foreach ($value as $k => $v) {
                    if ($this->filter->isAllowed($v, $k)) {
                        continue;
                    }
                    
                    $result[$k] = $v;
                }
                
                yield $key => $result;
            }
        } else {
            foreach ($stream as $key => $value) {
                $result = [];
                
                foreach ($value as $k => $v) {
                    if ($this->filter->isAllowed($v, $k)) {
                        $result[$k] = $v;
                    }
                }
                
                yield $key => $result;
            }
        }
    }
    
    public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof self
            && $other->negate === $this->negate
            && $other->filter->getMode() === $this->filter->getMode()
        ) {
            $this->filter = $this->negate
                ? $this->filter->or($other->filter)
                : $this->filter->and($other->filter);
            
            return true;
        }
        
        return false;
    }
}