<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Logic\FilterAND;
use FiiSoft\Jackdaw\Filter\Logic\FilterOR;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class ConditionalExtract extends BaseMapper
{
    private Filter $filter;
    private int $mode;
    private bool $negate;
    
    /**
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function __construct($filter, int $mode = Check::VALUE, bool $negate = false)
    {
        $this->filter = Filters::getAdapter($filter);
        $this->mode = Check::getMode($mode);
        $this->negate = $negate;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if (\is_iterable($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                if ($this->negate XOR $this->filter->isAllowed($v, $k, $this->mode)) {
                    $result[$k] = $v;
                }
            }
            
            return $result;
        }
    
        throw new \LogicException('Iterable value is required but got '.Helper::typeOfParam($value));
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