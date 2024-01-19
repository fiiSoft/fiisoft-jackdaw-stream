<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time;

use FiiSoft\Jackdaw\Filter\BaseFilter;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Time\Compare\CompoundTimeComp;
use FiiSoft\Jackdaw\Filter\Time\Compare\TimeComparator;
use FiiSoft\Jackdaw\Internal\Check;

abstract class TimeFilter extends BaseFilter
{
    protected TimeComparator $comparator;
    
    final public static function create(int $mode, TimeComparator $filter): Filter
    {
        if ($filter instanceof CompoundTimeComp) {
            $filter = $filter->optimise();
        }
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueTime($mode, $filter);
            case Check::KEY:
                return new KeyTime($mode, $filter);
            case Check::BOTH:
                return new BothTime($mode, $filter);
            case Check::ANY:
                return new AnyTime($mode, $filter);
            default:
                throw Check::invalidModeException($mode);
        }
    }
    
    final protected function __construct(int $mode, TimeComparator $comparator)
    {
        parent::__construct($mode);
        
        $this->comparator = $comparator;
    }
    
    final public function negate(): Filter
    {
        return static::create($this->mode, $this->comparator->negation());
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->mode
            ? static::create($mode, $this->comparator)
            : $this;
    }
}