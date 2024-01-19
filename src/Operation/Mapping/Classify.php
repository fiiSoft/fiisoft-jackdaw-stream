<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Classify extends BaseOperation
{
    private Discriminator $discriminator;
    
    /**
     * @param DiscriminatorReady|callable|array $discriminator
     */
    public function __construct($discriminator)
    {
        $this->discriminator = Discriminators::getAdapter($discriminator);
    }
    
    public function handle(Signal $signal): void
    {
        $signal->item->key = $this->discriminator->classify($signal->item->value, $signal->item->key);
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $this->discriminator->classify($value, $key) => $value;
        }
    }
}