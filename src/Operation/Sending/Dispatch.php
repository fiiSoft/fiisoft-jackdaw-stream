<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\DispatchOperation;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\HandlerReady;

final class Dispatch extends DispatchOperation
{
    private Discriminator $discriminator;
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param HandlerReady[] $handlers
     */
    public function __construct($discriminator, array $handlers)
    {
        parent::__construct($handlers);
        
        $this->discriminator = Discriminators::getAdapter($discriminator);
    }
    
    public function handle(Signal $signal): void
    {
        $classifier = $this->discriminator->classify($signal->item->value, $signal->item->key);
        if (\is_bool($classifier)) {
            $classifier = (int) $classifier;
        }
        
        if (isset($this->handlers[$classifier])) {
            $this->handlers[$classifier]->handle($signal);
        } else {
            throw OperationExceptionFactory::handlerIsNotDefined($classifier);
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $classifier = $this->discriminator->classify($value, $key);
            if (\is_bool($classifier)) {
                $classifier = (int) $classifier;
            }
            
            if (isset($this->handlers[$classifier])) {
                $this->handlers[$classifier]->handlePair($value, $key);
            } else {
                throw OperationExceptionFactory::handlerIsNotDefined($classifier);
            }
            
            yield $key => $value;
        }
    }
}