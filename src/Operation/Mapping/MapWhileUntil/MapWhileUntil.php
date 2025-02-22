<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\MapWhileUntil;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

abstract class MapWhileUntil extends BaseOperation
{
    protected Filter $condition;
    protected Mapper $mapper;
    
    protected bool $isActive = true;
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    final public function __construct($condition, $mapper)
    {
        $this->condition = Filters::getAdapter($condition);
        $this->mapper = Mappers::getAdapter($mapper);
    }
}