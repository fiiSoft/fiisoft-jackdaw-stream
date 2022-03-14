<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class Map extends BaseOperation
{
    private Mapper $mapper;
    
    /**
     * @param Mapper|Reducer|callable|mixed $mapper
     */
    public function __construct($mapper)
    {
        $this->mapper = Mappers::getAdapter($mapper);
    }
    
    public function handle(Signal $signal): void
    {
        $signal->item->value = $this->mapper->map($signal->item->value, $signal->item->key);
    
        $this->next->handle($signal);
    }
    
    public function mapper(): Mapper
    {
        return $this->mapper;
    }
}