<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\FieldValue;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Mapper\Value;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class MapKey extends BaseOperation
{
    private Mapper $mapper;
    
    /**
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $mapper
     */
    public function __construct($mapper)
    {
        $this->mapper = Mappers::getAdapter($mapper);
    }
    
    public function handle(Signal $signal): void
    {
        $signal->item->key = $this->mapper->map($signal->item->value, $signal->item->key);
    
        $this->next->handle($signal);
    }
    
    /**
     * @return bool return true when mapper from other MapKey has been merged
     */
    public function mergeWith(MapKey $other): bool
    {
        if ($this->mapper instanceof Value && $other->mapper instanceof FieldValue
            || $this->mapper instanceof FieldValue && $other->mapper instanceof Value
        ) {
            $this->mapper = $other->mapper;
            return true;
        }
        
        return $this->mapper->mergeWith($other->mapper);
    }
    
    public function mapper(): Mapper
    {
        return $this->mapper;
    }
}