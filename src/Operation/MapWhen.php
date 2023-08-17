<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Operation\Internal\ConditionalMapOperation;

final class MapWhen extends ConditionalMapOperation
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
    
        if ($this->condition->isTrueFor($item->value, $item->key)) {
            $item->value = $this->mapper->map($item->value, $item->key);
        } elseif ($this->elseMapper !== null) {
            $item->value = $this->elseMapper->map($item->value, $item->key);
        }
    
        $this->next->handle($signal);
    }
    
    public function getMaper(): Mapper
    {
        return $this->mapper;
    }
}