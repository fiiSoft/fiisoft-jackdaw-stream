<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class MapMany extends BaseOperation
{
    /** @var Mapper[] */
    private array $mappers = [];
    
    public function __construct(Map $first, Map $second)
    {
        $this->add($first);
        $this->add($second);
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        foreach ($this->mappers as $mapper) {
            $item->value = $mapper->map($item->value, $item->key);
        }
        
        $this->next->handle($signal);
    }
    
    public function add(Map $other): void
    {
        $this->mappers[] = $other->mapper();
    }
}