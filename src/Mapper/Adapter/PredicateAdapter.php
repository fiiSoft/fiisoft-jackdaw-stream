<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Adapter;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class PredicateAdapter extends BaseMapper
{
    private Predicate $predicate;
    
    public function __construct(Predicate $predicate)
    {
        $this->predicate = $predicate;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if (\is_iterable($value)) {
            $result = [];
        
            foreach ($value as $k => $v) {
                if ($this->predicate->isSatisfiedBy($v, $k)) {
                    $result[$k] = $v;
                }
            }
        
            return $result;
        }
    
        throw new \LogicException(
            'Unable to map '.Helper::typeOfParam($value).' using Predicate because it is not iterable'
        );
    }
}