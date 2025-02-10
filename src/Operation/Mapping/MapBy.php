<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class MapBy extends BaseOperation
{
    private Discriminator $discriminator;
    
    /** @var Mapper[] */
    private array $mappers = [];
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param array<string|int, MapperReady|callable|iterable|mixed> $mappers
     */
    public function __construct($discriminator, array $mappers)
    {
        if (empty($mappers)) {
            throw InvalidParamException::byName('mappers');
        }
        
        foreach ($mappers as $id => $mapper) {
            $this->mappers[$id] = Mappers::getAdapter($mapper);
        }
        
        $this->discriminator = Discriminators::getAdapter($discriminator);
    }
    
    public function handle(Signal $signal): void
    {
        $classifier = $this->discriminator->classify($signal->item->value, $signal->item->key);
        
        if (isset($this->mappers[$classifier])) {
            $signal->item->value = $this->mappers[$classifier]->map($signal->item->value, $signal->item->key);
        } else {
            throw OperationExceptionFactory::mapperIsNotDefined($classifier);
        }
    
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $classifier = $this->discriminator->classify($value, $key);
            
            if (isset($this->mappers[$classifier])) {
                yield $key => $this->mappers[$classifier]->map($value, $key);
            } else {
                throw OperationExceptionFactory::mapperIsNotDefined($classifier);
            }
        }
    }
}