<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;

final class MapWhen extends ConditionalMap
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
    
        if ($this->condition->isAllowed($item->value, $item->key)) {
            $item->value = $this->mapper->map($item->value, $item->key);
        } elseif ($this->elseMapper !== null) {
            $item->value = $this->elseMapper->map($item->value, $item->key);
        }
    
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->condition->isAllowed($value, $key)) {
                yield $key => $this->mapper->map($value, $key);
            } elseif ($this->elseMapper !== null) {
                yield $key => $this->elseMapper->map($value, $key);
            } else {
                yield $key => $value;
            }
        }
    }
    
    public function getMaper(): Mapper
    {
        return $this->mapper;
    }
}