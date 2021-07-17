<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class MapKey extends BaseOperation
{
    /** @var Mapper */
    private $mapper;
    
    /**
     * @param Mapper|callable $mapper
     */
    public function __construct($mapper)
    {
        $this->mapper = Mappers::getAdapter($mapper);
    }
    
    public function handle(Signal $signal)
    {
        $signal->item->key = $this->mapper->map($signal->item->value, $signal->item->key);
    
        $this->next->handle($signal);
    }
}